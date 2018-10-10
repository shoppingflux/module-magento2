<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Category;

use Magento\Catalog\Model\Category as CatalogCategory;
use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface as BaseStoreManagerInterface;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Category as FeedCategory;
use ShoppingFeed\Manager\Model\Feed\Product\CategoryFactory as FeedCategoryFactory;

class Selector implements SelectorInterface
{
    /**
     * @var BaseStoreManagerInterface
     */
    private $baseStoreManager;

    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var FeedCategoryFactory
     */
    private $feedCategoryFactory;

    /**
     * @var array[]
     */
    private $storeCategoryTree = [];

    /**
     * @var FeedCategory[][]
     */
    private $storeCategoryList = [];

    /**
     * @param BaseStoreManagerInterface $baseStoreManager
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param FeedCategoryFactory $feedCategoryFactory
     */
    public function __construct(
        BaseStoreManagerInterface $baseStoreManager,
        CategoryCollectionFactory $categoryCollectionFactory,
        FeedCategoryFactory $feedCategoryFactory
    ) {
        $this->baseStoreManager = $baseStoreManager;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->feedCategoryFactory = $feedCategoryFactory;
    }

    /**
     * @param StoreInterface $store
     * @return FeedCategory[]
     * @throws LocalizedException
     */
    private function getStoreCategoryList(StoreInterface $store)
    {
        $storeId = $store->getBaseStoreId();

        if (!isset($this->storeCategoryList[$storeId])) {
            $this->storeCategoryList[$storeId] = [];
            $baseStoreGroup = $this->baseStoreManager->getGroup($store->getBaseStore()->getStoreGroupId());
            $rootCategoryId = $baseStoreGroup->getRootCategoryId();

            $categoryCollection = $this->categoryCollectionFactory->create();
            $categoryCollection->setStoreId($storeId);
            $categoryCollection->addPathFilter('^' . CatalogCategory::TREE_ROOT_ID . '/' . $rootCategoryId);
            $categoryCollection->addNameToResult();
            $categoryCollection->addUrlRewriteToResult();
            $categoryCollection->addAttributeToSelect('is_active');

            /** @var CatalogCategory $category */
            foreach ($categoryCollection as $category) {
                $feedCategory = $this->feedCategoryFactory->create([ 'catalogCategory' => $category ]);
                $this->storeCategoryList[$storeId][$category->getId()] = $feedCategory;
            }
        }

        return $this->storeCategoryList[$storeId];
    }

    /**
     * @param StoreInterface $store
     * @return array
     * @throws LocalizedException
     */
    public function getStoreCategoryTree(StoreInterface $store)
    {
        $storeId = $store->getBaseStoreId();

        if (!isset($this->storeCategoryTree[$storeId])) {
            $baseStoreGroup = $this->baseStoreManager->getGroup($store->getBaseStore()->getStoreGroupId());
            $rootCategoryId = $baseStoreGroup->getRootCategoryId();

            $categoryList = $this->getStoreCategoryList($store);
            $categoryTree = [];

            foreach ($categoryList as $category) {
                $categoryId = $category->getId();
                $parentId = $category->getParentId();

                if (!isset($categoryTree[$categoryId])) {
                    $categoryTree[$categoryId] = [ 'value' => $categoryId ];
                }

                if (!isset($categoryTree[$parentId])) {
                    $categoryTree[$parentId] = [ 'value' => $parentId ];
                }

                $categoryTree[$categoryId]['label'] = $category->getName();
                $categoryTree[$categoryId]['is_active'] = $category->isActive();
                $categoryTree[$parentId]['optgroup'][] = &$categoryTree[$categoryId];
            }

            $this->storeCategoryTree[$storeId] = $categoryTree[$rootCategoryId]['optgroup'] ?? [];
        }

        return $this->storeCategoryTree[$storeId];
    }

    /**
     * @param FeedCategory $category
     * @param FeedCategory[] $categories
     * @return FeedCategory[]
     */
    protected function getCategoryPath(FeedCategory $category, array $categories)
    {
        $categoryPath = [ $category ];
        $parentLevel = $category->getLevel() - 1;
        $parentId = $category->getParentId();

        while ($parentId && ($parentLevel >= 2) && isset($categories[$parentId])) {
            $categoryPath[] = $categories[$parentId];
            $parentId = $categories[$parentId]->getParentId();
            $parentLevel--;
        }

        return $categoryPath;
    }

    /**
     * @param FeedCategory $category
     * @param int[] $selectionIds
     * @param string $selectionMode
     * @return bool
     */
    private function isSelectableCategory(FeedCategory $category, array $selectionIds, $selectionMode)
    {
        if (!$category->isActive()) {
            return false;
        }

        $isSelected = in_array($category->getId(), $selectionIds, true);
        return ($selectionMode === self::SELECTION_MODE_INCLUDE) ? $isSelected : !$isSelected;
    }

    public function getCatalogProductCategoryPath(
        CatalogProduct $product,
        StoreInterface $store,
        $preselectedCategoryId,
        array $selectionIds,
        $selectionMode,
        $maximumLevel = PHP_INT_MAX,
        $levelWeightMultiplier = 1,
        $useParentCategories = false,
        $includableParentCount = 1,
        $minimumParentLevel = 1,
        $parentWeightMultiplier = 1
    ) {
        $categories = $this->getStoreCategoryList($store);
        $categoryIds = $product->getCategoryIds();
        $selectedCategoryId = null;

        if (!empty($preselectedCategoryId)
            && isset($categories[$preselectedCategoryId])
            && $this->isSelectableCategory($categories[$preselectedCategoryId], $selectionIds, $selectionMode)
        ) {
            $selectedCategoryId = $preselectedCategoryId;
        } else {
            $categoryWeights = [];

            foreach ($categoryIds as $categoryId) {
                if (isset($categories[$categoryId])
                    && ($categories[$categoryId]->getLevel() <= $maximumLevel)
                    && $this->isSelectableCategory($categories[$categoryId], $selectionIds, $selectionMode)
                ) {
                    $categoryWeights[$categoryId] = $categories[$categoryId]->getLevel() * $levelWeightMultiplier;
                }
            }

            if ($useParentCategories) {
                foreach ($categoryIds as $categoryId) {
                    if (isset($categories[$categoryId])) {
                        $parentLevel = $categories[$categoryId]->getLevel() - 1;
                        $parentCount = 0;
                        $parentId = $categories[$categoryId]->getParentId();

                        while ($parentId
                            && isset($categories[$parentId])
                            && ($parentLevel-- >= $minimumParentLevel)
                            && (++$parentCount <= $includableParentCount)
                        ) {
                            if (!isset($categoryWeights[$parentId])
                                && $this->isSelectableCategory($categories[$parentId], $selectionIds, $selectionMode)
                            ) {
                                $categoryWeights[$parentId] = $parentLevel
                                    * $levelWeightMultiplier
                                    * $parentWeightMultiplier;
                            }

                            $parentId = $categories[$parentId]->getParentId();
                        }
                    }
                }
            }

            if (empty($categoryWeights)) {
                return null;
            }

            arsort($categoryWeights, SORT_NUMERIC);
            reset($categoryWeights);
            $selectedCategoryId = key($categoryWeights);
        }

        return $this->getCategoryPath($categories[$selectedCategoryId], $categories);
    }
}
