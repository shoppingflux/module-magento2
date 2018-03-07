<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Category;

use Magento\Catalog\Model\Product as CatalogProduct;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Category as FeedCategory;


interface SelectorInterface
{
    /**
     * @param CatalogProduct $product
     * @param StoreInterface $storeInterface
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
        $maximumLevel = PHP_INT_MAX,
        $levelWeightMultiplier = 1,
        $useParentCategories = false,
        $includableParentCount = 1,
        $minimumParentLevel = 1,
        $parentWeightMultiplier = 1
    );
}
