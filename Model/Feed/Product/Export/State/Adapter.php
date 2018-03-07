<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Export\State;

use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product as FeedProduct;
use ShoppingFeed\Manager\Model\Feed\RefreshableProduct;
use ShoppingFeed\Manager\Model\Time\Helper as TimeHelper;


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
     * @param TimeHelper $timeHelper
     * @param Config $config
     */
    public function __construct(TimeHelper $timeHelper, Config $config)
    {
        $this->timeHelper = $timeHelper;
        $this->config = $config;
    }

    public function requiresLoadedProduct(StoreInterface $store)
    {
        return true;
    }

    /**
     * @return string[]
     */
    public function getExportedProductTypes()
    {
        return [ ProductType::TYPE_SIMPLE, ConfigurableType::TYPE_CODE ];
    }

    /**
     * @param CatalogProduct $product
     * @return bool
     */
    public function isOutOfStockProduct(CatalogProduct $product)
    {
        return !$product->isInStock();
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

    public function getProductExportStates(StoreInterface $store, RefreshableProduct $product)
    {
        $feedProduct = $product->getFeedProduct();
        $catalogProduct = $product->getCatalogProduct();
        $baseExportState = FeedProduct::STATE_EXPORTED;
        $childExportState = FeedProduct::STATE_EXPORTED;

        if (!in_array($catalogProduct->getTypeId(), $this->getExportedProductTypes(), true)) {
            $baseExportState = FeedProduct::STATE_NEVER_EXPORTED;
            $childExportState = FeedProduct::STATE_NEVER_EXPORTED;
        } elseif (
            ($this->config->shouldExportNotSalable($store) || !$this->isNotSalableProduct($catalogProduct))
            && ($this->config->shouldExportOutOfStock($store) || !$this->isOutOfStockProduct($catalogProduct))
        ) {
            if (($this->config->shouldExportSelectedOnly($store) && !$feedProduct->isSelected())
                || !in_array((int) $catalogProduct->getVisibility(), $this->config->getExportedVisibilities($store))
            ) {
                $baseExportState = FeedProduct::STATE_NOT_EXPORTED;
            }
        } else {
            $baseExportState = FeedProduct::STATE_NOT_EXPORTED;
            $childExportState = FeedProduct::STATE_NOT_EXPORTED;
        }

        if (FeedProduct::STATE_EXPORTED !== $baseExportState) {
            $baseExportState = !$this->isRetainableProduct($store, $feedProduct)
                ? $baseExportState
                : FeedProduct::STATE_RETAINED;
        }

        return [ $baseExportState, $childExportState ];
    }
}
