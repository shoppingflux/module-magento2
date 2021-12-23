<?php

namespace ShoppingFeed\Manager\Model\Feed;

use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\CatalogInventory\Model\Stock\Item as StockItem;
use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Api\Data\Feed\ProductInterface as FeedProductInterface;
use ShoppingFeed\Manager\Model\Feed\Product\MptDetector;
use ShoppingFeed\Manager\Model\Feed\Product\Section\TypePoolInterface as SectionTypePoolInterface;
use ShoppingFeed\Manager\Model\Feed\Product\SectionFilterFactory;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Type\Stock as StockSectionType;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Config\StockInterface as StockSectionConfigInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Stock\QtyResolverInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Account\StoreFactory as StoreResourceFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\Collection as StoreCollection;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\CollectionFactory as StoreCollectionFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Refresher as RefresherResource;
use ShoppingFeed\Manager\Model\ShoppingFeed\Api\SessionManager as ApiSessionManager;
use ShoppingFeed\Sdk\Api\Catalog\InventoryUpdate as ApiInventoryUpdate;

class RealTimeUpdater
{
    /**
     * @var ApiSessionManager
     */
    private $apiSessionManager;

    /**
     * @var SectionTypePoolInterface
     */
    private $sectionTypePool;

    /**
     * @var ProductFilterFactory
     */
    private $productFilterFactory;

    /**
     * @var SectionFilterFactory
     */
    private $sectionFilterFactory;

    /**
     * @var Refresher
     */
    private $refresher;

    /**
     * @var RefresherResource
     */
    private $refresherResource;

    /**
     * @var Exporter
     */
    private $exporter;

    /**
     * @var StoreResourceFactory
     */
    private $storeResourceFactory;

    /**
     * @var StoreCollectionFactory
     */
    private $storeCollectionFactory;

    /**
     * @var QtyResolverInterface
     */
    private $qtyResolver;

    /**
     * @var MptDetector
     */
    private $mptDetector;

    /**
     * @var StoreCollection|null
     */
    private $storeCollection = null;

    /**
     * @param ApiSessionManager $apiSessionManager
     * @param SectionTypePoolInterface $sectionTypePool
     * @param ProductFilterFactory $productFilterFactory
     * @param SectionFilterFactory $sectionFilterFactory
     * @param Refresher $refresher
     * @param RefresherResource $refresherResource
     * @param Exporter $exporter
     * @param StoreResourceFactory $storeResourceFactory
     * @param StoreCollectionFactory $storeCollectionFactory
     * @param QtyResolverInterface $qtyResolver
     * @param MptDetector $mptDetector
     */
    public function __construct(
        ApiSessionManager $apiSessionManager,
        SectionTypePoolInterface $sectionTypePool,
        ProductFilterFactory $productFilterFactory,
        SectionFilterFactory $sectionFilterFactory,
        Refresher $refresher,
        RefresherResource $refresherResource,
        Exporter $exporter,
        StoreResourceFactory $storeResourceFactory,
        StoreCollectionFactory $storeCollectionFactory,
        QtyResolverInterface $qtyResolver,
        MptDetector $mptDetector
    ) {
        $this->apiSessionManager = $apiSessionManager;
        $this->sectionTypePool = $sectionTypePool;
        $this->productFilterFactory = $productFilterFactory;
        $this->sectionFilterFactory = $sectionFilterFactory;
        $this->refresher = $refresher;
        $this->refresherResource = $refresherResource;
        $this->exporter = $exporter;
        $this->storeResourceFactory = $storeResourceFactory;
        $this->storeCollectionFactory = $storeCollectionFactory;
        $this->qtyResolver = $qtyResolver;
        $this->mptDetector = $mptDetector;
    }

    /**
     * @return StoreCollection
     */
    private function getStoreCollection()
    {
        if (null === $this->storeCollection) {
            $this->storeCollection = $this->storeCollectionFactory->create();
            $this->storeCollection->load();
        }

        return $this->storeCollection;
    }

    /**
     * @param int[] $productIds
     * @throws LocalizedException
     */
    public function handleProductsQuantityChange(array $productIds)
    {
        /**
         * When the Magento Performance Toolkit generates fake products, we do not want to fill the
         * "sfm_feed_product_section_type" (or other independent tables) as a result of syncing the product list.
         * Otherwise, the corresponding SQL request(s) will be collected, and the MPT will attempt to link the
         * "sfm_feed_product_section_type" table to the "catalog_product_entity" table, which is impossible...
         * (unfortunately, it does not seem to be possible to target the
         * @see \Magento\Setup\Model\FixtureGenerator\ProductGenerator::$customTableMap
         * property from the DI to prevent this in a cleaner manner).
         */

        $productIds = array_diff(
            $productIds,
            $this->mptDetector->getMagentoPerformanceToolkitFakeProductIds($productIds)
        );

        if (empty($productIds)) {
            return;
        }

        $stockSectionType = $this->sectionTypePool->getTypeByCode(StockSectionType::CODE);
        $stockSectionTypeId = $stockSectionType->getId();
        $stockSectionConfig = $stockSectionType->getConfig();

        $productFilter = $this->productFilterFactory->create();
        $productFilter->setProductIds($productIds);

        $sectionFilter = $this->sectionFilterFactory->create();
        $sectionFilter->setTypeIds([ $stockSectionTypeId ]);

        $this->refresherResource->forceProductExportStateRefresh(
            FeedProductInterface::REFRESH_STATE_REQUIRED,
            clone $productFilter
        );

        $this->refresherResource->forceProductSectionRefresh(
            FeedProductInterface::REFRESH_STATE_REQUIRED,
            clone $sectionFilter,
            clone $productFilter
        );

        foreach ($this->getStoreCollection() as $store) {
            if (
                ($stockSectionConfig instanceof StockSectionConfigInterface)
                && $stockSectionConfig->shouldUpdateQuantityInRealTime($store)
            ) {
                try {
                    $this->refresher->refreshProducts(
                        $store,
                        $productFilter,
                        [ $stockSectionTypeId => $productFilter ],
                        [ $stockSectionTypeId => $sectionFilter ]
                    );

                    $updatableProducts = $this->exporter->exportStoreProducts($store, $productIds);

                    $apiStore = $this->apiSessionManager->getStoreApiResource($store);
                    $inventoryApi = $apiStore->getInventoryApi();
                    $inventoryUpdate = new ApiInventoryUpdate();

                    foreach ($updatableProducts as $updatableProduct) {
                        $inventoryUpdate->add($updatableProduct->getReference(), $updatableProduct->getQuantity());
                    }

                    $inventoryApi->execute($inventoryUpdate);
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
    }

    /**
     * @param CatalogProduct $product
     */
    public function handleCatalogProductSave(CatalogProduct $product)
    {
        if (
            (!$productId = (int) $product->getId())
            /** @see handleProductsQuantityChange() */
            || $this->mptDetector->isMagentoPerformanceToolkitFakeProduct($product)
        ) {
            return;
        }

        $storeResource = $this->storeResourceFactory->create();
        $storeCollection = $this->getStoreCollection();

        foreach ($storeCollection->getLoadedIds() as $storeId) {
            $storeResource->synchronizeFeedProductList((int) $storeId, [ $productId ]);
        }

        $productFilter = $this->productFilterFactory->create();
        $productFilter->setProductIds([ $productId ]);

        $sectionFilter = $this->sectionFilterFactory->create();
        $sectionFilter->setProductIds([ $productId ]);

        $this->refresherResource->forceProductExportStateRefresh(
            FeedProductInterface::REFRESH_STATE_REQUIRED,
            $productFilter
        );

        $this->refresherResource->forceProductSectionRefresh(
            FeedProductInterface::REFRESH_STATE_REQUIRED,
            $sectionFilter,
            $productFilter
        );
    }

    /**
     * @param StockItem $stockItem
     * @throws LocalizedException
     */
    public function handleStockItemSave(StockItem $stockItem)
    {
        if (
            (!$productId = (int) $stockItem->getProductId())
            || $this->qtyResolver->isUsingMsi()
            /** @see handleProductsQuantityChange() */
            || $this->mptDetector->isMagentoPerformanceToolkitFakeProductId($productId)
        ) {
            return;
        }

        $this->handleProductsQuantityChange([ $productId ]);
    }
}
