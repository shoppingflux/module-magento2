<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Config;

use Magento\Eav\Model\Entity\Attribute\Source\SourceInterface as EavAttributeSourceInterface;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Form\Element\DataType\Number as UiNumber;
use Magento\Ui\Component\Form\Element\DataType\Text as UiText;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Config\Field\Category\MultiSelect as CategoryMultiSelect;
use ShoppingFeed\Manager\Model\Config\Field\Checkbox;
use ShoppingFeed\Manager\Model\Config\Field\MultiSelect;
use ShoppingFeed\Manager\Model\Config\Field\Select;
use ShoppingFeed\Manager\Model\Config\Field\TextBox;
use ShoppingFeed\Manager\Model\Config\FieldFactoryInterface;
use ShoppingFeed\Manager\Model\Config\FieldInterface;
use ShoppingFeed\Manager\Model\Config\Value\Handler\Number as NumberHandler;
use ShoppingFeed\Manager\Model\Config\Value\Handler\Option as OptionHandler;
use ShoppingFeed\Manager\Model\Config\Value\Handler\PositiveInteger as PositiveIntegerHandler;
use ShoppingFeed\Manager\Model\Config\Value\HandlerFactoryInterface as ValueHandlerFactoryInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\SourceInterface as AttributeSourceInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Category\SelectorInterface as CategorySelectorInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractConfig;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Config\Value\Handler\Attribute as AttributeHandler;

class Categories extends AbstractConfig implements CategoriesInterface
{
    const KEY_USE_ATTRIBUTE_VALUE = 'use_attribute_value';
    const KEY_CATEGORY_ATTRIBUTE = 'category_attribute';
    const KEY_CATEGORY_NAME_TYPE = 'category_name_type';
    const KEY_CATEGORY_SELECTION_IDS = 'category_selection_ids';
    const KEY_CATEGORY_ATTRIBUTE_SELECTION_IDS = 'category_attribute_selection_ids';
    const KEY_INCLUDE_SUB_CATEGORIES_IN_SELECTION = 'include_sub_categories_in_selection';
    const KEY_CATEGORY_SELECTION_MODE = 'category_selection_mode';
    const KEY_MAXIMUM_CATEGORY_LEVEL = 'maximum_category_level';
    const KEY_LEVEL_WEIGHT_MULTIPLIER = 'level_weight_multiplier';
    const KEY_USE_PARENT_CATEGORIES = 'use_parent_categories';
    const KEY_INCLUDABLE_PARENT_COUNT = 'includable_parent_count';
    const KEY_MINIMUM_PARENT_LEVEL = 'minimum_parent_level';
    const KEY_PARENT_WEIGHT_MULTIPLIER = 'parent_weight_multiplier';
    const KEY_TIE_BREAKING_SELECTION = 'tie_breaking_selection';

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
     * @var AttributeSourceInterface
     */
    private $renderableAttributeSource;

    /**
     * @param FieldFactoryInterface $fieldFactory
     * @param ValueHandlerFactoryInterface $valueHandlerFactory
     * @param Registry $coreRegistry
     * @param StoreManagerInterface $storeManager
     * @param CategorySelectorInterface $categorySelector
     * @param AttributeSourceInterface $renderableAttributeSource
     */
    public function __construct(
        FieldFactoryInterface $fieldFactory,
        ValueHandlerFactoryInterface $valueHandlerFactory,
        Registry $coreRegistry,
        StoreManagerInterface $storeManager,
        CategorySelectorInterface $categorySelector,
        AttributeSourceInterface $renderableAttributeSource
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->storeManager = $storeManager;
        $this->categorySelector = $categorySelector;
        $this->renderableAttributeSource = $renderableAttributeSource;
        parent::__construct($fieldFactory, $valueHandlerFactory);
    }

    protected function getBaseFields()
    {
        $numberHandler = $this->valueHandlerFactory->create(NumberHandler::TYPE_CODE);

        $positiveIntegerHandler = $this->valueHandlerFactory->create(PositiveIntegerHandler::TYPE_CODE);

        $renderableAttributeHandler = $this->valueHandlerFactory->create(
            AttributeHandler::TYPE_CODE,
            [ 'attributeSource' => $this->renderableAttributeSource ]
        );

        $categoryNameTypeHandler = $this->valueHandlerFactory->create(
            OptionHandler::TYPE_CODE,
            [
                'dataType' => UiText::NAME,
                'optionArray' => [
                    [
                        'label' => __('Name'),
                        'value' => static::CATEGORY_NAME_TYPE_NAME,
                    ],
                    [
                        'label' => __('Breadcrumbs'),
                        'value' => static::CATEGORY_NAME_TYPE_BREADCRUMBS,
                    ],
                ],
            ]
        );

        $tieBreakingSelectionHandler = $this->valueHandlerFactory->create(
            OptionHandler::TYPE_CODE,
            [
                'dataType' => UiText::NAME,
                'optionArray' => [
                    [
                        'label' => __('Undetermined'),
                        'value' => CategorySelectorInterface::TIE_BREAKING_SELECTION_UNDETERMINED,
                    ],
                    [
                        'label' => __('First in the Tree'),
                        'value' => CategorySelectorInterface::TIE_BREAKING_SELECTION_FIRST_IN_TREE,
                    ],
                    [
                        'label' => __('Last in the Tree'),
                        'value' => CategorySelectorInterface::TIE_BREAKING_SELECTION_LAST_IN_TREE,
                    ],
                ],
            ]
        );

        return array_merge(
            [
                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_USE_ATTRIBUTE_VALUE,
                        'label' => __('Use Attribute Value Instead of Product Categories'),
                        'checkedNotice' => __(
                            'The value of the chosen attribute will be exported as the product category.'
                        ),
                        'uncheckedNotice' => __(
                            'The exported category for each product will be determined using the options below.'
                        ),
                        'checkedDependentFieldNames' => [
                            self::KEY_CATEGORY_ATTRIBUTE,
                            self::KEY_CATEGORY_ATTRIBUTE_SELECTION_IDS,
                        ],
                        'uncheckedDependentFieldNames' => [
                            self::KEY_CATEGORY_NAME_TYPE,
                            self::KEY_CATEGORY_SELECTION_IDS,
                            self::KEY_INCLUDE_SUB_CATEGORIES_IN_SELECTION,
                            self::KEY_MAXIMUM_CATEGORY_LEVEL,
                            self::KEY_LEVEL_WEIGHT_MULTIPLIER,
                            self::KEY_USE_PARENT_CATEGORIES,
                            self::KEY_INCLUDABLE_PARENT_COUNT,
                            self::KEY_MINIMUM_PARENT_LEVEL,
                            self::KEY_PARENT_WEIGHT_MULTIPLIER,
                            self::KEY_TIE_BREAKING_SELECTION,
                        ],
                        'sortOrder' => 10,
                    ]
                ),

                $this->fieldFactory->create(
                    Select::TYPE_CODE,
                    [
                        'name' => self::KEY_CATEGORY_ATTRIBUTE,
                        'valueHandler' => $renderableAttributeHandler,
                        'isRequired' => true,
                        'label' => __('Category Attribute'),
                        'sortOrder' => 20,
                    ]
                ),

                $this->fieldFactory->create(
                    Select::TYPE_CODE,
                    [
                        'name' => self::KEY_CATEGORY_NAME_TYPE,
                        'valueHandler' => $categoryNameTypeHandler,
                        'isRequired' => true,
                        'defaultFormValue' => static::CATEGORY_NAME_TYPE_BREADCRUMBS,
                        'defaultUseValue' => static::CATEGORY_NAME_TYPE_BREADCRUMBS,
                        'label' => __('Exported Category Name'),
                        'sortOrder' => 30,
                    ]
                ),

                // Category selection IDs

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_INCLUDE_SUB_CATEGORIES_IN_SELECTION,
                        'label' => __('Include Sub-Categories in Selection'),
                        'sortOrder' => 50,
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_CATEGORY_SELECTION_MODE,
                        'label' => __('Category Selection Mode'),
                        'checkedLabel' => __('Include'),
                        'uncheckedLabel' => __('Exclude'),
                        'checkedNotice' => __('Only the selected categories will be considered.'),
                        'uncheckedNotice' => __('The selected categories will not be considered.'),
                        'sortOrder' => 60,
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
                        'sortOrder' => 70,
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
                                __(
                                    'The number that will be multiplied with the level of a category to determine its weight.'
                                ),
                                __(
                                    'The category with the highest weight among all the considered categories will be selected.'
                                ),
                            ]
                        ),
                        'sortOrder' => 80,
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
                        'sortOrder' => 100,
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
                        'notice' => __(
                            'For each category of a given product, the number of its most immediate parents that will also be considered.'
                        ),
                        'sortOrder' => 110,
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
                            ' ',
                            [
                                __('Only parent categories with a greater or equal level will be considered.'),
                                __('The root category has a level of 1.'),
                            ]
                        ),
                        'sortOrder' => 120,
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
                        'sortOrder' => 130,
                    ]
                ),

                $this->fieldFactory->create(
                    Select::TYPE_CODE,
                    [
                        'name' => self::KEY_TIE_BREAKING_SELECTION,
                        'valueHandler' => $tieBreakingSelectionHandler,
                        'isRequired' => true,
                        'defaultFormValue' => CategorySelectorInterface::TIE_BREAKING_SELECTION_FIRST_IN_TREE,
                        'defaultUseValue' => CategorySelectorInterface::TIE_BREAKING_SELECTION_FIRST_IN_TREE,
                        'label' => __('Selection in Case of Ties'),
                        'notice' => implode(
                            "\n",
                            [
                                __('Without changes to the categories, the selections are stable.'),
                                __('For better predictability, choose "First" or "Last in the Tree".'),
                                __('For more stability in the case of changes, limit the available choices by:'),
                                __('- choosing above the categories to include or exclude from the selection,'),
                                __('- defining the "Forced Category" on the product pages.'),
                            ]
                        ),
                        'sortOrder' => 140,
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
        $selectionFields = [
            $this->fieldFactory->create(
                CategoryMultiSelect::TYPE_CODE,
                [
                    'name' => self::KEY_CATEGORY_SELECTION_IDS,
                    'categoryTree' => $this->categorySelector->getStoreCategoryTree($store),
                    'label' => __('Category Selection'),
                    'sortOrder' => 40,
                ]
            ),
        ];

        if (
            ($categoryAttribute = $this->getCategoryAttribute($store))
            && $categoryAttribute->usesSource()
            && ($categoryAttributeSource = $categoryAttribute->getSource())
            && ($categoryAttributeSource instanceof EavAttributeSourceInterface)
        ) {
            $categoryAttributeOptions = $categoryAttributeSource->getAllOptions(false);

            $selectionFields[] = $this->fieldFactory->create(
                MultiSelect::TYPE_CODE,
                [
                    'name' => self::KEY_CATEGORY_ATTRIBUTE_SELECTION_IDS,
                    'valueHandler' => $this->valueHandlerFactory->create(
                        OptionHandler::TYPE_CODE,
                        [
                            'dataType' => UiNumber::NAME,
                            'optionArray' => $categoryAttributeOptions,
                        ]
                    ),
                    'label' => __('Category Selection'),
                    'notice' => __('After selecting a new attribute, save your changes to refresh the options.'),
                    'sortOrder' => 40,
                ]
            );
        }

        return array_merge(
            $selectionFields,
            parent::getStoreFields($store)
        );
    }

    public function getFieldsetLabel()
    {
        return __('Feed - Categories Section');
    }

    public function shouldUseAttributeValue(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_USE_ATTRIBUTE_VALUE);
    }

    public function getCategoryAttribute(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_CATEGORY_ATTRIBUTE);
    }

    public function getCategoryNameType(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_CATEGORY_NAME_TYPE);
    }

    public function getCategorySelectionIds(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_CATEGORY_SELECTION_IDS) ?? [];
    }

    public function getCategoryAttributeSelectionIds(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_CATEGORY_ATTRIBUTE_SELECTION_IDS) ?? [];
    }

    public function shouldIncludeSubCategoriesInSelection(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_INCLUDE_SUB_CATEGORIES_IN_SELECTION);
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

    public function getTieBreakingSelection(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_TIE_BREAKING_SELECTION);
    }
}
