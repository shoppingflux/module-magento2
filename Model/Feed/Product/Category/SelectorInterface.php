<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Category;

use Magento\Catalog\Model\Product as CatalogProduct;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Category as FeedCategory;


interface SelectorInterface
{
    const SELECTION_MODE_EXCLUDE = 'exclude';
    const SELECTION_MODE_INCLUDE = 'include';

    /**
     * @param StoreInterface $store
     * @return array
     */
    public function getStoreCategoryTree(StoreInterface $store);

    /**
     * @param CatalogProduct $product
     * @param StoreInterface $storeInterface
     * @param int|null $preselectedCategoryId
     * @param int[] $selectionIds
     * @param string $selectionMode
     * @param int $maximumLevel
     * @param int $levelWeightMultiplier
     * @param bool $useParentCategories
     * @param int $includableParentCount
     * @param int $minimumParentLevel
     * @param int $parentWeightMultiplier
     * @return FeedCategory[]|null
     */
    public function getCatalogProductCategoryPath(
        CatalogProduct $product,
        StoreInterface $storeInterface,
        $preselectedCategoryId,
        array $selectionIds,
        $selectionMode,
        $maximumLevel = PHP_INT_MAX,
        $levelWeightMultiplier = 1,
        $useParentCategories = false,
        $includableParentCount = 1,
        $minimumParentLevel = 1,
        $parentWeightMultiplier = 1
    );
}
