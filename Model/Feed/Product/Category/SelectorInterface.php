<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Category;

use Magento\Catalog\Model\Product as CatalogProduct;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Category as FeedCategory;

interface SelectorInterface
{
    const SELECTION_MODE_EXCLUDE = 'exclude';
    const SELECTION_MODE_INCLUDE = 'include';

    const TIE_BREAKING_SELECTION_UNDETERMINED = 'undetermined';
    const TIE_BREAKING_SELECTION_FIRST_IN_TREE = 'first_in_tree';
    const TIE_BREAKING_SELECTION_LAST_IN_TREE = 'last_in_tree';

    /**
     * @param StoreInterface $store
     * @return array
     */
    public function getStoreCategoryTree(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @param int[] $selectionIds
     * @param bool $includeSubCategoriesInSelection
     * @param string $selectionMode
     * @param int $maximumLevel
     * @return int[]
     */
    public function getStoreSelectableCategoryIds(
        StoreInterface $store,
        array $selectionIds,
        $includeSubCategoriesInSelection,
        $selectionMode,
        $maximumLevel = PHP_INT_MAX
    );

    /**
     * @param CatalogProduct $product
     * @param StoreInterface $storeInterface
     * @param int|null $preselectedCategoryId
     * @param int[] $selectionIds
     * @param bool $includeSubCategoriesInSelection
     * @param string $selectionMode
     * @param int $maximumLevel
     * @param int $levelWeightMultiplier
     * @param bool $useParentCategories
     * @param int $includableParentCount
     * @param int $minimumParentLevel
     * @param int $parentWeightMultiplier
     * @param string $tieBreakingSelection
     * @return FeedCategory[]|null
     */
    public function getCatalogProductCategoryPath(
        CatalogProduct $product,
        StoreInterface $storeInterface,
        $preselectedCategoryId,
        array $selectionIds,
        $includeSubCategoriesInSelection,
        $selectionMode,
        $maximumLevel = PHP_INT_MAX,
        $levelWeightMultiplier = 1,
        $useParentCategories = false,
        $includableParentCount = 1,
        $minimumParentLevel = 1,
        $parentWeightMultiplier = 1,
        $tieBreakingSelection = self::TIE_BREAKING_SELECTION_UNDETERMINED
    );
}
