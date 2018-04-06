<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Config;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Account\Store\Config\AbstractField;
use ShoppingFeed\Manager\Model\Account\Store\RegistryConstants;
use ShoppingFeed\Manager\Model\Account\Store\Config\Field\Category\MultiSelect as CategoryMultiSelect;
use ShoppingFeed\Manager\Model\Account\Store\Config\Field\Checkbox;
use ShoppingFeed\Manager\Model\Account\Store\Config\Field\TextBox;
use ShoppingFeed\Manager\Model\Account\Store\Config\Value\Handler\Number as NumberHandler;
use ShoppingFeed\Manager\Model\Account\Store\Config\Value\Handler\PositiveInteger as PositiveIntegerHandler;
use ShoppingFeed\Manager\Model\Feed\Product\Category\SelectorInterface as CategorySelector;
use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractConfig;


class Categories extends AbstractConfig implements CategoriesInterface
{
    const KEY_CATEGORY_SELECTION_IDS = 'category_selection_ids';
    const KEY_CATEGORY_SELECTION_MODE = 'category_selection_mode';
    const KEY_MAXIMUM_CATEGORY_LEVEL = 'maximum_category_level';
    const KEY_LEVEL_WEIGHT_MULTIPLIER = 'level_weight_multiplier';
    const KEY_USE_PARENT_CATEGORIES = 'use_parent_categories';
    const KEY_INCLUDABLE_PARENT_COUNT = 'includable_parent_count';
    const KEY_MINIMUM_PARENT_LEVEL = 'minimum_parent_level';
    const KEY_PARENT_WEIGHT_MULTIPLIER = 'parent_weight_multiplier';

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @param Registry $coreRegistry
     * @param CategoryCollectionFactory $categoryCollectionFactory
     */
    public function __construct(Registry $coreRegistry, CategoryCollectionFactory $categoryCollectionFactory)
    {
        $this->coreRegistry = $coreRegistry;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * @throws LocalizedException
     */
    private function getSelectableCategoryCollection()
    {
        /** @var CategoryCollection $baseCategoryCollection */
        $baseCategoryCollection = $this->categoryCollectionFactory->create();

        $accountStore = $this->coreRegistry->registry(RegistryConstants::CURRENT_ACCOUNT_STORE);
        $baseStoreId = !empty($accountStore)
            ? $accountStore->getBaseStoreId()
            : $baseCategoryCollection->getDefaultStoreId();

        $baseCategoryCollection->addAttributeToFilter('entity_id', [ 'neq' => Category::TREE_ROOT_ID ]);
        $baseCategoryCollection->addAttributeToSelect('path');
        $baseCategoryCollection->setStoreId($baseStoreId);

        $selectableCategoryIds = [];

        /** @var Category $category */
        foreach ($baseCategoryCollection as $category) {
            foreach (explode('/', $category->getPath()) as $parentId) {
                $selectableCategoryIds[$parentId] = 1;
            }
        }

        /* @var CategoryCollection $selectableCategoryCollection */
        $selectableCategoryCollection = $this->categoryCollectionFactory->create();
        $selectableCategoryIds = array_keys($selectableCategoryIds);
        $selectableCategoryCollection->addAttributeToFilter('entity_id', [ 'in' => $selectableCategoryIds ]);
        $selectableCategoryCollection->addAttributeToSelect([ 'parent_id', 'name', 'is_active' ]);
        $selectableCategoryCollection->setStoreId($baseStoreId);

        return $selectableCategoryCollection;
    }

    /**
     * @return AbstractField[]
     * @throws LocalizedException
     */
    protected function getBaseFields()
    {
        // @todo use factories (bonus: keyword arguments rather than positional)

        return array_merge(
            [
                new CategoryMultiSelect(
                    self::KEY_CATEGORY_SELECTION_IDS,
                    $this->getSelectableCategoryCollection(),
                    __('Category Selection')
                ),

                new Checkbox(
                    self::KEY_CATEGORY_SELECTION_MODE,
                    __('Category Selection Mode'),
                    false,
                    __('Only the selected categories will be considered.'),
                    __('The selected categories will not be considered.'),
                    [],
                    [],
                    __('Include'),
                    __('Exclude')
                ),

                new TextBox(
                    self::KEY_MAXIMUM_CATEGORY_LEVEL,
                    new PositiveIntegerHandler(),
                    __('Maximum Category Level'),
                    false,
                    null,
                    PHP_INT_MAX,
                    __('Only categories with a lesser or equal level will be considered.')
                    . ' '
                    . __('Leave empty if all the categories of a given product should be considered.')
                    . ' '
                    . __('The root category has a level of 1.')
                ),

                new TextBox(
                    self::KEY_LEVEL_WEIGHT_MULTIPLIER,
                    new NumberHandler(),
                    __('Level Weight Multiplier'),
                    true,
                    1,
                    1,
                    __('The number that will be multiplied with the level of a category to determine its weight.')
                    . ' '
                    . __('The category with the highest weight among all the considered categories will be selected.')
                ),

                new Checkbox(
                    self::KEY_USE_PARENT_CATEGORIES,
                    __('Use Parent Categories'),
                    false,
                    __('Whether parent categories should also be considered.'),
                    __('Whether parent categories should also be considered.'),
                    [
                        self::KEY_INCLUDABLE_PARENT_COUNT,
                        self::KEY_MINIMUM_PARENT_LEVEL,
                        self::KEY_PARENT_WEIGHT_MULTIPLIER,
                    ]
                ),

                new TextBox(
                    self::KEY_INCLUDABLE_PARENT_COUNT,
                    new PositiveIntegerHandler(),
                    __('Includable Parent Count'),
                    true,
                    1,
                    1,
                    __('For each category of a given product, the number of its most immediate parents that will also be considered.')
                ),

                new TextBox(
                    self::KEY_MINIMUM_PARENT_LEVEL,
                    new PositiveIntegerHandler(),
                    __('Minimum Parent Level'),
                    true,
                    2,
                    2,
                    __('Only parent categories with a greater or equal level will be considered.')
                    . ' '
                    . __('The root category has a level of 1.')
                ),

                new TextBox(
                    self::KEY_PARENT_WEIGHT_MULTIPLIER,
                    new NumberHandler(),
                    __('Parent Weight Multiplier'),
                    true,
                    1,
                    1,
                    __('The multiplier that will additionally be used to determine the weights of parent categories.')
                ),
            ],
            parent::getBaseFields()
        );
    }

    public function getFieldsetLabel()
    {
        return __('Feed - Categories Section');
    }

    public function getCategorySelectionIds(StoreInterface $store)
    {
        return $this->getStoreFieldValue($store, self::KEY_CATEGORY_SELECTION_IDS);
    }

    public function getCategorySelectionMode(StoreInterface $store)
    {
        return $this->getStoreFieldValue($store, self::KEY_CATEGORY_SELECTION_MODE)
            ? CategorySelector::SELECTION_MODE_INCLUDE
            : CategorySelector::SELECTION_MODE_EXCLUDE;
    }

    public function getMaximumCategoryLevel(StoreInterface $store)
    {
        return $this->getStoreFieldValue($store, self::KEY_MAXIMUM_CATEGORY_LEVEL);
    }

    public function getLevelWeightMultiplier(StoreInterface $store)
    {
        return $this->getStoreFieldValue($store, self::KEY_LEVEL_WEIGHT_MULTIPLIER);
    }

    public function shouldUseParentCategories(StoreInterface $store)
    {
        return $this->getStoreFieldValue($store, self::KEY_USE_PARENT_CATEGORIES);
    }

    public function getIncludableParentCount(StoreInterface $store)
    {
        return $this->getStoreFieldValue($store, self::KEY_INCLUDABLE_PARENT_COUNT);
    }

    public function getMinimumParentLevel(StoreInterface $store)
    {
        return $this->getStoreFieldValue($store, self::KEY_MINIMUM_PARENT_LEVEL);
    }

    public function getParentWeightMultiplier(StoreInterface $store)
    {
        return $this->getStoreFieldValue($store, self::KEY_PARENT_WEIGHT_MULTIPLIER);
    }
}
