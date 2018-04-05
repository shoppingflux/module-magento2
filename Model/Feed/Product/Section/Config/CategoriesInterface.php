<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Config;

use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Account\Store;
use ShoppingFeed\Manager\Model\Feed\Product\Section\ConfigInterface;


interface CategoriesInterface extends ConfigInterface
{
    /**
     * @param StoreInterface $store
     * @return int[]
     */
    public function getCategorySelectionIds(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return string
     */
    public function getCategorySelectionMode(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return int
     */
    public function getMaximumCategoryLevel(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return float
     */
    public function getLevelWeightMultiplier(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldUseParentCategories(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return int
     */
    public function getIncludableParentCount(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return int
     */
    public function getMinimumParentLevel(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return float
     */
    public function getParentWeightMultiplier(StoreInterface $store);
}
