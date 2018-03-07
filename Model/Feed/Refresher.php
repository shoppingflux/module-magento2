<?php

namespace ShoppingFeed\Manager\Model\Feed;

use Magento\Catalog\Api\ProductRepositoryInterface as CatalogProductRepository;
use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as CatalogProductCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product as FeedProduct;
use ShoppingFeed\Manager\Model\Feed\Product\Export\State\AdapterInterface as ExportStateAdapter;
use ShoppingFeed\Manager\Model\Feed\Product\Filter as FeedProductFilter;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Filter as FeedSectionFilter;
use ShoppingFeed\Manager\Model\Feed\Product\Section\TypePoolInterface as SectionTypePool;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product as FeedProductResource;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\ProductFactory as FeedProductResourceFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product\Section as FeedSectionResource;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product\SectionFactory as FeedSectionResourceFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Refresher as RefresherResource;


class Refresher
{
    const REFRESH_STATE_UP_TO_DATE = 1;
    const REFRESH_STATE_ADVISED = 2;
    const REFRESH_STATE_REQUIRED = 3;

    /**
     * @var RefresherResource
     */
    private $refresherResource;

    /**
     * @var SectionTypePool
     */
    private $sectionTypePool;

    /**
     * @var ExportStateAdapter
     */
    private $exportStateAdapter;

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
    protected $catalogProductCollectionFactory;


    /**
     * @param RefresherResource $refresherResource
     * @param ExportStateAdapter $exportStateAdapter
     * @param SectionTypePool $sectionTypePool
     * @param FeedProductResourceFactory $feedProductResourceFactory
     * @param FeedSectionResourceFactory $feedSectionResourceFactory
     * @param CatalogProductRepository $catalogProductRepository
     * @param CatalogProductCollectionFactory $catalogProductCollectionFactory
     */
    public function __construct(
        RefresherResource $refresherResource,
        ExportStateAdapter $exportStateAdapter,
        SectionTypePool $sectionTypePool,
        FeedProductResourceFactory $feedProductResourceFactory,
        FeedSectionResourceFactory $feedSectionResourceFactory,
        CatalogProductRepository $catalogProductRepository,
        CatalogProductCollectionFactory $catalogProductCollectionFactory
    ) {
        $this->refresherResource = $refresherResource;
        $this->exportStateAdapter = $exportStateAdapter;
        $this->sectionTypePool = $sectionTypePool;
        $this->feedProductResource = $feedProductResourceFactory->create();
        $this->feedSectionResource = $feedSectionResourceFactory->create();
        $this->catalogProductRepository = $catalogProductRepository;
        $this->catalogProductCollectionFactory = $catalogProductCollectionFactory;
    }

    /**
     * @param RefreshableProduct[] $refreshableProducts
     * @param StoreInterface $store
     * @param bool $refreshExportState
     * @param array $refreshableSectionTypeIds
     */
    protected function refreshProductsWithCollection(
        array $refreshableProducts,
        StoreInterface $store,
        $refreshExportState,
        array $refreshableSectionTypeIds
    ) {
        // @todo
        $productCollection = $this->catalogProductCollectionFactory->create();
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
            $productId = $refreshableProduct->getId();
            $storeId = $store->getId();

            /** @var CatalogProduct $catalogProduct */
            $catalogProduct = $this->catalogProductRepository->getById(
                $productId,
                false,
                $store->getBaseStoreId(),
                true
            );

            $refreshableProduct->setCatalogProduct($catalogProduct, true);

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
                    self::REFRESH_STATE_UP_TO_DATE,
                    (FeedProduct::STATE_RETAINED !== $baseExportState),
                    (FeedProduct::STATE_RETAINED !== $previousBaseExportState)
                );
            }

            // @todo do not refresh a section if the new export state is not in its filtered export states?
            // @todo (but via another option, otherwise certainly too restrictive)

            foreach ($refreshableSectionTypeIds as $typeId) {
                $sectionType = $this->sectionTypePool->getTypeById($typeId);
                $sectionData = $sectionType->getAdapter()->getProductData($store, $refreshableProduct);

                $this->feedSectionResource->updateSectionData(
                    $sectionType->getId(),
                    $productId,
                    $storeId,
                    $sectionData,
                    self::REFRESH_STATE_UP_TO_DATE
                );
            }
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
        $refreshableSliceSize = 5000; // @todo customizable
        $maximumRefreshedCount = 1000000; // @todo customizable (+ maximum time threshold)

        while ($refreshedProductCount < $maximumRefreshedCount) {
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

            // Find out the most efficient way to refresh the products from the current slice.

            $isProductLoadingRequiredForSections = false;
            $isProductLoadingRequiredForExportState = $this->exportStateAdapter->requiresLoadedProduct($store);

            foreach ($sliceRefreshableSectionTypeIds as $typeId) {
                $sectionType = $this->sectionTypePool->getTypeById($typeId);

                if ($sectionType->getAdapter()->requiresLoadedProduct($store)) {
                    $isProductLoadingRequiredForSections = true;
                    break;
                }
            }

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

        return $this;
    }
}
