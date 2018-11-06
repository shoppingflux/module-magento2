<?php

namespace ShoppingFeed\Manager\Model\Feed;

use Magento\Catalog\Api\ProductRepositoryInterface as CatalogProductRepository;
use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Catalog\Model\ResourceModel\Product\Collection as CatalogProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as CatalogProductCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\App\Emulation as AppEmulation;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\DataObject;
use ShoppingFeed\Manager\Model\Feed\Product as FeedProduct;
use ShoppingFeed\Manager\Model\Feed\Product\AdapterInterface as ProductAdapterInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Export\State\AdapterInterface as ExportStateAdapterInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Export\State\ConfigInterface as ExportStateConfigInterface;
use ShoppingFeed\Manager\Model\Feed\ProductFilter as FeedProductFilter;
use ShoppingFeed\Manager\Model\Feed\ProductFilterFactory as FeedProductFilterFactory;
use ShoppingFeed\Manager\Model\Feed\Product\SectionFilter as FeedSectionFilter;
use ShoppingFeed\Manager\Model\Feed\Product\SectionFilterFactory as FeedSectionFilterFactory;
use ShoppingFeed\Manager\Model\Feed\Product\Section\TypePoolInterface as SectionTypePoolInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product as FeedProductResource;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\ProductFactory as FeedProductResourceFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product\Section as FeedSectionResource;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product\SectionFactory as FeedSectionResourceFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Refresher as RefresherResource;

class Refresher
{
    // @todo getters + configuration data
    const PRODUCT_COLLECTION_PAGE_SIZE = 1000;
    const REFRESHABLE_SLICE_SIZE = 5000;

    const MAXIMUM_REFRESHED_COUNT = 1000000;
    const MAXIMUM_TIME_SPENT = 1800;

    const PRODUCT_COLLECTION_STOCK_STATUS_FILTER_FLAG = 'has_stock_status_filter';

    /**
     * @var AppEmulation
     */
    private $appEmulation;

    /**
     * @var RefresherResource
     */
    private $refresherResource;

    /**
     * @var SectionTypePoolInterface
     */
    private $sectionTypePool;

    /**
     * @var ExportStateAdapterInterface
     */
    private $exportStateAdapter;

    /**
     * @var ExportStateConfigInterface
     */
    private $exportStateConfig;

    /**
     * @var FeedProductFilterFactory
     */
    private $feedProductFilterFactory;

    /**
     * @var FeedSectionFilterFactory
     */
    private $feedSectionFilterFactory;

    /**
     * @var FeedProductResource
     */
    private $feedProductResource;

    /**
     * @var FeedSectionResource
     */
    private $feedSectionResource;

    /**
     * @var CatalogProductRepository
     */
    private $catalogProductRepository;

    /**
     * @var CatalogProductCollectionFactory
     */
    private $catalogProductCollectionFactory;

    /**
     * @param AppEmulation $appEmulation
     * @param RefresherResource $refresherResource
     * @param ExportStateAdapterInterface $exportStateAdapter
     * @param ExportStateConfigInterface $exportStateConfig
     * @param SectionTypePoolInterface $sectionTypePool
     * @param ProductFilterFactory $feedProductFilterFactory
     * @param FeedSectionFilterFactory $feedSectionFilterFactory
     * @param FeedProductResourceFactory $feedProductResourceFactory
     * @param FeedSectionResourceFactory $feedSectionResourceFactory
     * @param CatalogProductRepository $catalogProductRepository
     * @param CatalogProductCollectionFactory $catalogProductCollectionFactory
     */
    public function __construct(
        AppEmulation $appEmulation,
        RefresherResource $refresherResource,
        ExportStateAdapterInterface $exportStateAdapter,
        ExportStateConfigInterface $exportStateConfig,
        SectionTypePoolInterface $sectionTypePool,
        FeedProductFilterFactory $feedProductFilterFactory,
        FeedSectionFilterFactory $feedSectionFilterFactory,
        FeedProductResourceFactory $feedProductResourceFactory,
        FeedSectionResourceFactory $feedSectionResourceFactory,
        CatalogProductRepository $catalogProductRepository,
        CatalogProductCollectionFactory $catalogProductCollectionFactory
    ) {
        $this->appEmulation = $appEmulation;
        $this->refresherResource = $refresherResource;
        $this->exportStateAdapter = $exportStateAdapter;
        $this->exportStateConfig = $exportStateConfig;
        $this->sectionTypePool = $sectionTypePool;
        $this->feedProductFilterFactory = $feedProductFilterFactory;
        $this->feedSectionFilterFactory = $feedSectionFilterFactory;
        $this->feedProductResource = $feedProductResourceFactory->create();
        $this->feedSectionResource = $feedSectionResourceFactory->create();
        $this->catalogProductRepository = $catalogProductRepository;
        $this->catalogProductCollectionFactory = $catalogProductCollectionFactory;
    }

    /**
     * @param StoreInterface $store
     * @param DataObject $oldConfiguration
     * @param DataObject $newConfiguration
     */
    public function forceOutdatedStoreProductSectionsRefresh(
        StoreInterface $store,
        DataObject $oldConfiguration,
        DataObject $newConfiguration
    ) {
        $productFilter = $this->feedProductFilterFactory->create();
        $productFilter->setStoreIds([ $store->getId() ]);

        if ($this->exportStateConfig->isRefreshNeededForStoreDataChange($store, $oldConfiguration, $newConfiguration)) {
            $this->refresherResource->forceProductExportStateRefresh(
                FeedProduct::REFRESH_STATE_REQUIRED,
                $productFilter
            );
        }

        $outdatedSectionTypeIds = [];

        foreach ($this->sectionTypePool->getTypes() as $sectionType) {
            $config = $sectionType->getConfig();

            if ($config->isRefreshNeededForStoreDataChange($store, $oldConfiguration, $newConfiguration)) {
                $outdatedSectionTypeIds[] = $sectionType->getId();
            }
        }

        if (!empty($outdatedSectionTypeIds)) {
            $sectionFilter = $this->feedSectionFilterFactory->create();
            $sectionFilter->setStoreIds([ $store->getId() ]);
            $sectionFilter->setTypeIds($outdatedSectionTypeIds);

            $this->refresherResource->forceProductSectionRefresh(
                FeedProduct::REFRESH_STATE_REQUIRED,
                $sectionFilter,
                $productFilter
            );
        }
    }

    /**
     * @param RefreshableProduct $refreshableProduct
     * @param StoreInterface $store
     * @param bool $refreshExportState
     * @param array $refreshableSectionTypeIds
     * @throws LocalizedException
     */
    private function refreshProduct(
        RefreshableProduct $refreshableProduct,
        StoreInterface $store,
        $refreshExportState,
        array $refreshableSectionTypeIds
    ) {
        $productId = $refreshableProduct->getId();
        $storeId = $store->getId();
        $refreshableProduct->getCatalogProduct()->setStoreId($store->getBaseStoreId());

        if ($refreshExportState) {
            $previousBaseExportState = $refreshableProduct->getFeedProduct()->getExportState();

            list($baseExportState, $childExportState) = $this->exportStateAdapter->getProductExportStates(
                $store,
                $refreshableProduct
            );

            $this->feedProductResource->updateProductExportStates(
                $productId,
                $storeId,
                $baseExportState,
                $childExportState,
                FeedProduct::REFRESH_STATE_UP_TO_DATE,
                (FeedProduct::STATE_RETAINED !== $baseExportState),
                (FeedProduct::STATE_RETAINED !== $previousBaseExportState)
            );
        }

        foreach ($refreshableSectionTypeIds as $typeId) {
            $sectionType = $this->sectionTypePool->getTypeById($typeId);
            $sectionData = $sectionType->getAdapter()->getProductData($store, $refreshableProduct);

            $this->feedSectionResource->updateSectionData(
                $sectionType->getId(),
                $productId,
                $storeId,
                $sectionData,
                FeedProduct::REFRESH_STATE_UP_TO_DATE
            );
        }
    }

    /**
     * @param CatalogProductCollection $productCollection
     * @param ProductAdapterInterface $productAdapter
     * @param StoreInterface $store
     * @throws LocalizedException
     */
    private function applyAdapterToLoadableProductCollection(
        CatalogProductCollection $productCollection,
        ProductAdapterInterface $productAdapter,
        StoreInterface $store
    ) {
        $productAdapter->prepareLoadableProductCollection($store, $productCollection);

        if ($productCollection->isLoaded()) {
            throw new LocalizedException(
                __(
                    'Product adapter "%1" must not load the given collection in prepareLoadableProductCollection().',
                    get_class($productAdapter)
                )
            );
        }
    }

    /**
     * @param RefreshableProduct[] $refreshableProducts
     * @param StoreInterface $store
     * @param bool $refreshExportState
     * @param array $refreshableSectionTypeIds
     * @throws LocalizedException
     */
    protected function refreshProductsWithCollection(
        array $refreshableProducts,
        StoreInterface $store,
        $refreshExportState,
        array $refreshableSectionTypeIds
    ) {
        /** @var ProductAdapterInterface[] $sectionAdapters */
        $sectionAdapters = [];

        foreach ($refreshableSectionTypeIds as $typeId) {
            $sectionAdapters[] = $this->sectionTypePool->getTypeById($typeId)->getAdapter();
        }

        $productCollection = $this->catalogProductCollectionFactory->create();
        $productCollection->setStoreId($store->getBaseStoreId());

        // Depending on the configuration, the Magento_CatalogInventory module may unwantedly filter the collection.
        $productCollection->setFlag(static::PRODUCT_COLLECTION_STOCK_STATUS_FILTER_FLAG, true);

        if ($refreshExportState) {
            $this->applyAdapterToLoadableProductCollection($productCollection, $this->exportStateAdapter, $store);
        }

        foreach ($sectionAdapters as $sectionAdapter) {
            $this->applyAdapterToLoadableProductCollection($productCollection, $sectionAdapter, $store);
        }

        $refreshableProductSlices = array_chunk($refreshableProducts, static::PRODUCT_COLLECTION_PAGE_SIZE);

        foreach ($refreshableProductSlices as $refreshableProductSlice) {
            $sliceProductCollection = clone $productCollection;
            $sliceProductIds = [];

            /** @var RefreshableProduct $refreshableProduct */
            foreach ($refreshableProductSlice as $refreshableProduct) {
                $sliceProductIds[] = $refreshableProduct->getId();
            }

            $sliceProductCollection->addIdFilter($sliceProductIds);
            $sliceProductCollection->load();

            foreach ($sliceProductCollection as $catalogProduct) {
                $catalogProduct->setData('website_ids', $catalogProduct->getData('websites'));
            }

            if ($refreshExportState) {
                $this->exportStateAdapter->prepareLoadedProductCollection($store, $sliceProductCollection);
            }

            foreach ($sectionAdapters as $sectionAdapter) {
                $sectionAdapter->prepareLoadedProductCollection($store, $sliceProductCollection);
            }

            foreach ($refreshableProductSlice as $refreshableProduct) {
                /** @var CatalogProduct $catalogProduct */
                if ($catalogProduct = $sliceProductCollection->getItemById($refreshableProduct->getId())) {
                    $refreshableProduct->setCatalogProduct($catalogProduct, false);
                    $this->refreshProduct($refreshableProduct, $store, $refreshExportState, $refreshableSectionTypeIds);
                }
            }
        }
    }

    /**
     * @param RefreshableProduct[] $refreshableProducts
     * @param StoreInterface $store
     * @param bool $refreshExportState
     * @param array $refreshableSectionTypeIds
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    protected function refreshProductsWithRepository(
        array $refreshableProducts,
        StoreInterface $store,
        $refreshExportState,
        array $refreshableSectionTypeIds
    ) {
        foreach ($refreshableProducts as $refreshableProduct) {
            /** @var CatalogProduct $catalogProduct */
            $catalogProduct = $this->catalogProductRepository->getById(
                $refreshableProduct->getId(),
                false,
                $store->getBaseStoreId(),
                true
            );

            $refreshableProduct->setCatalogProduct($catalogProduct, true);
            $this->refreshProduct($refreshableProduct, $store, $refreshExportState, $refreshableSectionTypeIds);
        }
    }

    /**
     * @param StoreInterface $store
     * @param FeedProductFilter|null $exportStateRefreshProductFilter
     * @param FeedProductFilter[] $refreshedSectionTypeProductFilters
     * @param FeedSectionFilter[] $refreshedSectionTypeSectionFilters
     * @return $this
     * @throws LocalizedException
     */
    public function refreshProducts(
        StoreInterface $store,
        FeedProductFilter $exportStateRefreshProductFilter = null,
        array $refreshedSectionTypeProductFilters = [],
        array $refreshedSectionTypeSectionFilters = []
    ) {
        $this->appEmulation->startEnvironmentEmulation($store->getBaseStoreId());
        $this->feedProductResource->startExportStateUpdateBatching();
        $this->feedSectionResource->startSectionDataUpdateBatching();

        $isExportStateRefreshed = (null !== $exportStateRefreshProductFilter);

        $refreshedSectionTypeIds = array_intersect(
            array_keys($refreshedSectionTypeProductFilters),
            array_keys($refreshedSectionTypeSectionFilters)
        );

        if (!$isExportStateRefreshed && empty($refreshedSectionTypeIds)) {
            return $this;
        }

        $sortedRefreshedSectionTypeIds = [];

        foreach ($refreshedSectionTypeIds as $key => $typeId) {
            $sectionType = $this->sectionTypePool->getTypeById($typeId);
            $sortedRefreshedSectionTypeIds[$sectionType->getSortOrder()] = $typeId;
        }

        ksort($sortedRefreshedSectionTypeIds, SORT_NUMERIC);
        $sortedRefreshedSectionTypeIds = array_values($sortedRefreshedSectionTypeIds);

        $refreshedProductCount = 0;
        $refreshableSliceSize = static::REFRESHABLE_SLICE_SIZE;
        $maximumRefreshedCount = static::MAXIMUM_REFRESHED_COUNT;
        $startTime = time();

        while ($refreshedProductCount < $maximumRefreshedCount) {
            $currentTime = time();

            if ($currentTime - $startTime > static::MAXIMUM_TIME_SPENT) {
                break;
            }

            $refreshableProducts = $this->refresherResource->getRefreshableProducts(
                $store->getId(),
                $exportStateRefreshProductFilter,
                $sortedRefreshedSectionTypeIds,
                $refreshedSectionTypeProductFilters,
                $refreshedSectionTypeSectionFilters,
                $refreshableSliceSize
            );

            if (empty($refreshableProducts)) {
                break;
            }

            $refreshedProductCount += count($refreshableProducts);

            // Check which parts are to be refreshed for the products of the current slice.

            $refreshSliceExportState = false;
            $sliceNonRefreshableSectionTypeIds = $refreshedSectionTypeIds;

            /** @var RefreshableProduct $refreshableProduct */
            foreach ($refreshableProducts as $refreshableProduct) {
                if ($isExportStateRefreshed
                    && !$refreshSliceExportState
                    && $refreshableProduct->hasRefreshableExportState()
                ) {
                    $refreshSliceExportState = true;
                }

                foreach ($sliceNonRefreshableSectionTypeIds as $key => $typeId) {
                    if ($refreshableProduct->hasRefreshableSectionType($typeId)) {
                        unset($sliceNonRefreshableSectionTypeIds[$key]);
                    }
                }

                if (empty($sliceNonRefreshableSectionTypeIds)
                    && (!$isExportStateRefreshed || $refreshSliceExportState)
                ) {
                    break;
                }
            }

            $sliceRefreshableSectionTypeIds = array_diff(
                $refreshedSectionTypeIds,
                $sliceNonRefreshableSectionTypeIds
            );

            // Find out the most efficient strategies to refresh the products from the current slice.

            $isProductLoadingRequiredForSections = false;
            $isProductLoadingRequiredForExportState =
                $this->exportStateAdapter->requiresLoadedProduct($store)
                || $this->exportStateConfig->shouldForceProductLoadForRefresh($store);

            foreach ($sliceRefreshableSectionTypeIds as $typeId) {
                $sectionType = $this->sectionTypePool->getTypeById($typeId);

                if ($sectionType->getAdapter()->requiresLoadedProduct($store)
                    || $sectionType->getConfig()->shouldForceProductLoadForRefresh($store)
                ) {
                    $isProductLoadingRequiredForSections = true;
                    break;
                }
            }

            // Refresh products.

            if ((!$refreshSliceExportState && !$isProductLoadingRequiredForSections)
                || ($refreshSliceExportState && !$isProductLoadingRequiredForExportState)
            ) {
                $this->refreshProductsWithCollection(
                    $refreshableProducts,
                    $store,
                    $refreshSliceExportState,
                    $isProductLoadingRequiredForSections ? [] : $sliceRefreshableSectionTypeIds
                );
            }

            if (($refreshSliceExportState && $isProductLoadingRequiredForExportState)
                || $isProductLoadingRequiredForSections
            ) {
                $this->refreshProductsWithRepository(
                    $refreshableProducts,
                    $store,
                    $refreshSliceExportState && $isProductLoadingRequiredForExportState,
                    $isProductLoadingRequiredForSections ? $sliceRefreshableSectionTypeIds : []
                );
            }
        }

        $this->feedProductResource->stopExportStateUpdateBatching();
        $this->feedSectionResource->stopSectionDataUpdateBatching();
        $this->appEmulation->stopEnvironmentEmulation();

        return $this;
    }
}
