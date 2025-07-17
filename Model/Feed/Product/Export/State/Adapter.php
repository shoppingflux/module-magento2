<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Export\State;

use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Catalog\Model\Product\Attribute\Source\Status as CatalogProductStatus;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\App\ObjectManager;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product as FeedProduct;
use ShoppingFeed\Manager\Model\Feed\Product\Stock\QtyResolverInterface;
use ShoppingFeed\Manager\Model\Feed\RefreshableProduct;
use ShoppingFeed\Manager\Model\TimeHelper;

class Adapter implements AdapterInterface
{
    /**
     * @var TimeHelper
     */
    private $timeHelper;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var QtyResolverInterface
     */
    private $qtyResolver;

    /**
     * @var StoreInterface|null
     */
    private $currentStore = null;

    /**
     * @param TimeHelper $timeHelper
     * @param Config $config
     * @param QtyResolverInterface|null $qtyResolver
     */
    public function __construct(TimeHelper $timeHelper, Config $config, ?QtyResolverInterface $qtyResolver = null)
    {
        $this->timeHelper = $timeHelper;
        $this->config = $config;
        $this->qtyResolver = $qtyResolver ?? ObjectManager::getInstance()->get(QtyResolverInterface::class);
    }

    public function requiresLoadedProduct(StoreInterface $store)
    {
        return true;
    }

    public function prepareLoadableProductCollection(StoreInterface $store, ProductCollection $productCollection)
    {
        // Despite the name, this method actually adds the website IDs to the products.
        $productCollection->addWebsiteNamesToResult();
    }

    public function prepareLoadedProductCollection(StoreInterface $store, ProductCollection $productCollection)
    {
    }

    /**
     * @return string[]
     */
    public function getExportableProductTypes()
    {
        return $this->config->getExportableProductTypes();
    }

    /**
     * @param StoreInterface $store
     * @return string[]
     */
    public function getExportedProductTypes(StoreInterface $store)
    {
        return $this->config->getExportedProductTypes($store);
    }

    /**
     * @param CatalogProduct $product
     * @return bool
     */
    public function isOutOfStockProduct(CatalogProduct $product)
    {
        if (null === $this->currentStore) {
            return !$product->isInStock();
        }

        return !$this->qtyResolver->isCatalogProductInStock(
            $product,
            $this->currentStore,
            QtyResolverInterface::MSI_QUANTITY_TYPE_SALABLE
        );
    }

    /**
     * @param CatalogProduct $product
     * @return bool
     */
    public function isDisabledProduct(CatalogProduct $product)
    {
        return ((int) $product->getStatus() !== CatalogProductStatus::STATUS_ENABLED);
    }

    /**
     * @param CatalogProduct $product
     * @return bool
     */
    public function isNotSalableProduct(CatalogProduct $product)
    {
        return !$product->isSalable();
    }

    /**
     * @param StoreInterface $store
     * @param FeedProduct $feedProduct
     * @return bool
     */
    protected function isOutdatedRetainedProduct(StoreInterface $store, FeedProduct $feedProduct)
    {
        $retentionStartTimestamp = $feedProduct->getExportRetentionStartedAtTimestamp();
        $maximumRetentionDuration = $this->config->getPreviouslyExportedRetentionDuration($store);
        return empty($retentionStartTimestamp)
            || ($this->timeHelper->utcTimestamp() - $retentionStartTimestamp > $maximumRetentionDuration);
    }

    /**
     * @param StoreInterface $store
     * @param FeedProduct $feedProduct
     * @return bool
     */
    protected function isRetainableProduct(StoreInterface $store, FeedProduct $feedProduct)
    {
        if ($this->config->shouldRetainPreviouslyExported($store)) {
            return ($feedProduct->getExportState() === FeedProduct::STATE_EXPORTED)
                || (($feedProduct->getExportState() === FeedProduct::STATE_RETAINED)
                    && !$this->isOutdatedRetainedProduct($store, $feedProduct));
        }

        return false;
    }

    /**
     * @param StoreInterface $store
     * @param RefreshableProduct $refreshableProduct
     * @return bool
     */
    private function isProductSelectedForExport(StoreInterface $store, RefreshableProduct $refreshableProduct)
    {
        return (!$attribute = $this->config->getIsSelectedProductAttribute($store))
            ? $refreshableProduct->getFeedProduct()->isSelected()
            : (bool) $refreshableProduct->getCatalogProduct()->getData($attribute->getAttributeCode());
    }

    public function getProductExportStates(StoreInterface $store, RefreshableProduct $product)
    {
        $this->currentStore = $store;

        $feedProduct = $product->getFeedProduct();
        $catalogProduct = $product->getCatalogProduct();
        $baseExportState = FeedProduct::STATE_NOT_EXPORTED;
        $childExportState = FeedProduct::STATE_NOT_EXPORTED;
        $exclusionReason = null;

        if (!in_array($catalogProduct->getTypeId(), $this->getExportableProductTypes(), true)) {
            $baseExportState = FeedProduct::STATE_NEVER_EXPORTED;
            $childExportState = FeedProduct::STATE_NEVER_EXPORTED;
            $exclusionReason = FeedProduct::EXCLUSION_REASON_UNHANDLED_PRODUCT_TYPE;
        } elseif (!in_array($catalogProduct->getTypeId(), $this->getExportedProductTypes($store), true)) {
            $exclusionReason = FeedProduct::EXCLUSION_REASON_FILTERED_PRODUCT_TYPE;
        } elseif (!in_array($store->getBaseStore()->getWebsiteId(), $catalogProduct->getWebsiteIds())) {
            $exclusionReason = FeedProduct::EXCLUSION_REASON_NOT_IN_WEBSITE;
        } elseif ($this->isNotSalableProduct($catalogProduct) && !$this->config->shouldExportNotSalable($store)) {
            $exclusionReason = FeedProduct::EXCLUSION_REASON_NOT_SALABLE;
        } elseif ($this->isOutOfStockProduct($catalogProduct) && !$this->config->shouldExportOutOfStock($store)) {
            $exclusionReason = FeedProduct::EXCLUSION_REASON_OUT_OF_STOCK;
        } elseif ($this->isDisabledProduct($catalogProduct) && !$this->config->shouldExportDisabled($store)) {
            $exclusionReason = FeedProduct::EXCLUSION_REASON_DISABLED;
        } else {
            $childExportState = FeedProduct::STATE_EXPORTED;

            if (!in_array((int) $catalogProduct->getVisibility(), $this->config->getExportedVisibilities($store))) {
                $exclusionReason = FeedProduct::EXCLUSION_REASON_FILTERED_VISIBILITY;
            } elseif (
                $this->config->shouldExportSelectedOnly($store)
                && !$this->isProductSelectedForExport($store, $product)
            ) {
                $exclusionReason = FeedProduct::EXCLUSION_REASON_UNSELECTED_PRODUCT;
            } else {
                $baseExportState = FeedProduct::STATE_EXPORTED;
            }
        }

        if (FeedProduct::STATE_EXPORTED !== $baseExportState) {
            $baseExportState = !$this->isRetainableProduct($store, $feedProduct)
                ? $baseExportState
                : FeedProduct::STATE_RETAINED;
        }

        return [ $baseExportState, $childExportState, $exclusionReason ];
    }
}
