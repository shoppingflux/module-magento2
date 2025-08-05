<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Config;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\ConfigInterface;

interface CategoriesInterface extends ConfigInterface
{
    const CATEGORY_NAME_TYPE_NAME = 'name';
    const CATEGORY_NAME_TYPE_BREADCRUMBS = 'breadcrumbs';

    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldUseAttributeValue(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return AbstractAttribute|null
     */
    public function getCategoryAttribute(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return string|null
     */
    public function getCategoryNameType(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return int[]
     */
    public function getCategorySelectionIds(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return int[]
     */
    public function getCategoryAttributeSelectionIds(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldIncludeSubCategoriesInSelection(StoreInterface $store);

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

    /**
     * @param StoreInterface $store
     * @return string
     */
    public function getTieBreakingSelection(StoreInterface $store);
}
