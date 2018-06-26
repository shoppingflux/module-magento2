<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Config;

use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Config\Field\Category\MultiSelect as CategoryMultiSelect;
use ShoppingFeed\Manager\Model\Config\Field\Checkbox;
use ShoppingFeed\Manager\Model\Config\Field\TextBox;
use ShoppingFeed\Manager\Model\Config\FieldFactoryInterface;
use ShoppingFeed\Manager\Model\Config\FieldInterface;
use ShoppingFeed\Manager\Model\Config\Value\Handler\Number as NumberHandler;
use ShoppingFeed\Manager\Model\Config\Value\Handler\PositiveInteger as PositiveIntegerHandler;
use ShoppingFeed\Manager\Model\Config\Value\HandlerFactoryInterface as ValueHandlerFactoryInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Category\SelectorInterface as CategorySelectorInterface;
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
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CategorySelectorInterface
     */
    private $categorySelector;

    /**
     * @param FieldFactoryInterface $fieldFactory
     * @param ValueHandlerFactoryInterface $valueHandlerFactory
     * @param Registry $coreRegistry
     * @param StoreManagerInterface $storeManager
     * @param CategorySelectorInterface $categorySelector
     */
    public function __construct(
        FieldFactoryInterface $fieldFactory,
        ValueHandlerFactoryInterface $valueHandlerFactory,
        Registry $coreRegistry,
        StoreManagerInterface $storeManager,
        CategorySelectorInterface $categorySelector
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->storeManager = $storeManager;
        $this->categorySelector = $categorySelector;
        parent::__construct($fieldFactory, $valueHandlerFactory);
    }

    protected function getBaseFields()
    {
        $numberHandler = $this->valueHandlerFactory->create(NumberHandler::TYPE_CODE);
        $positiveIntegerHandler = $this->valueHandlerFactory->create(PositiveIntegerHandler::TYPE_CODE);

        return array_merge(
            [
                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_CATEGORY_SELECTION_MODE,
                        'label' => __('Category Selection Mode'),
                        'checkedLabel' => __('Include'),
                        'uncheckedLabel' => __('Exclude'),
                        'checkedNotice' => __('Only the selected categories will be considered.'),
                        'uncheckedNotice' => __('The selected categories will not be considered.'),
                        'sortOrder' => 20,
                    ]
                ),

                $this->fieldFactory->create(
                    TextBox::TYPE_CODE,
                    [
                        'name' => self::KEY_MAXIMUM_CATEGORY_LEVEL,
                        'valueHandler' => $positiveIntegerHandler,
                        'defaultUseValue' => PHP_INT_MAX,
                        'label' => __('Maximum Category Level'),
                        'notice' => implode(
                            ' ',
                            [
                                __('Only categories with a lesser or equal level will be considered.'),
                                __('Leave empty if all the categories of a given product should be considered.'),
                                __('The root category has a level of 1.'),
                            ]
                        ),
                        'sortOrder' => 30,
                    ]
                ),

                $this->fieldFactory->create(
                    TextBox::TYPE_CODE,
                    [
                        'name' => self::KEY_LEVEL_WEIGHT_MULTIPLIER,
                        'valueHandler' => $numberHandler,
                        'isRequired' => true,
                        'defaultFormValue' => 1,
                        'defaultUseValue' => 1,
                        'label' => __('Level Weight Multiplier'),
                        'notice' => implode(
                            '',
                            [
                                __('The number that will be multiplied with the level of a category to determine its weight.'),
                                __('The category with the highest weight among all the considered categories will be selected.'),
                            ]
                        ),
                        'sortOrder' => 40,
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_USE_PARENT_CATEGORIES,
                        'label' => __('Use Parent Categories'),
                        'checkedNotice' => __('Whether parent categories should also be considered.'),
                        'uncheckedNotice' => __('Whether parent categories should also be considered.'),
                        'checkedDependentFieldNames' => [
                            self::KEY_INCLUDABLE_PARENT_COUNT,
                            self::KEY_MINIMUM_PARENT_LEVEL,
                            self::KEY_PARENT_WEIGHT_MULTIPLIER,
                        ],
                        'sortOrder' => 50,
                    ]
                ),

                $this->fieldFactory->create(
                    TextBox::TYPE_CODE,
                    [
                        'name' => self::KEY_INCLUDABLE_PARENT_COUNT,
                        'valueHandler' => $positiveIntegerHandler,
                        'isRequired' => true,
                        'defaultFormValue' => 1,
                        'defaultUseValue' => 1,
                        'label' => __('Includable Parent Count'),
                        'notice' => __('For each category of a given product, the number of its most immediate parents that will also be considered.'),
                        'sortOrder' => 60,
                    ]
                ),

                $this->fieldFactory->create(
                    TextBox::TYPE_CODE,
                    [
                        'name' => self::KEY_MINIMUM_PARENT_LEVEL,
                        'valueHandler' => $positiveIntegerHandler,
                        'isRequired' => true,
                        'defaultFormValue' => 2,
                        'defaultUseValue' => 2,
                        'label' => __('Minimum Parent Level'),
                        'notice' => implode(
                            '',
                            [
                                __('Only parent categories with a greater or equal level will be considered.'),
                                __('The root category has a level of 1.'),
                            ]
                        ),
                        'sortOrder' => 70,
                    ]
                ),

                $this->fieldFactory->create(
                    TextBox::TYPE_CODE,
                    [
                        'name' => self::KEY_PARENT_WEIGHT_MULTIPLIER,
                        'valueHandler' => $numberHandler,
                        'isRequired' => true,
                        'defaultFormValue' => 1,
                        'defaultUseValue' => 1,
                        'label' => __('Parent Weight Multiplier'),
                        'notice' => __(
                            'The multiplier that will additionally be used to determine the weights of parent categories.'
                        ),
                        'sortOrder' => 80,
                    ]
                ),
            ],
            parent::getBaseFields()
        );
    }

    /**
     * @param StoreInterface $store
     * @return FieldInterface[]
     */
    protected function getStoreFields(StoreInterface $store)
    {
        return array_merge(
            [
                $this->fieldFactory->create(
                    CategoryMultiSelect::TYPE_CODE,
                    [
                        'name' => self::KEY_CATEGORY_SELECTION_IDS,
                        'categoryTree' => $this->categorySelector->getStoreCategoryTree($store),
                        'label' => __('Category Selection'),
                        'sortOrder' => 10,
                    ]
                ),
            ],
            parent::getStoreFields($store)
        );
    }

    public function getFieldsetLabel()
    {
        return __('Feed - Categories Section');
    }

    public function getCategorySelectionIds(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_CATEGORY_SELECTION_IDS) ?? [];
    }

    public function getCategorySelectionMode(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_CATEGORY_SELECTION_MODE)
            ? CategorySelectorInterface::SELECTION_MODE_INCLUDE
            : CategorySelectorInterface::SELECTION_MODE_EXCLUDE;
    }

    public function getMaximumCategoryLevel(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_MAXIMUM_CATEGORY_LEVEL);
    }

    public function getLevelWeightMultiplier(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_LEVEL_WEIGHT_MULTIPLIER);
    }

    public function shouldUseParentCategories(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_USE_PARENT_CATEGORIES);
    }

    public function getIncludableParentCount(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_INCLUDABLE_PARENT_COUNT);
    }

    public function getMinimumParentLevel(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_MINIMUM_PARENT_LEVEL);
    }

    public function getParentWeightMultiplier(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_PARENT_WEIGHT_MULTIPLIER);
    }
}
