<?php

namespace ShoppingFeed\Manager\Ui\DataProvider\Catalog\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\Container as UiContainer;
use Magento\Ui\Component\Form\Element\Checkbox as UiCheckbox;
use Magento\Ui\Component\Form\Element\DataType\Boolean as UiBoolean;
use Magento\Ui\Component\Form\Field as UiField;
use Magento\Ui\Component\Form\Fieldset as UiFieldset;
use Magento\Ui\Component\Form\Element\Select as UiSelect;
use ShoppingFeed\Manager\Model\Account\Store;
use ShoppingFeed\Manager\Model\Feed\Product\Category\SelectorInterface as CategorySelectorInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Type\Categories as CategoriesSectionType;
use ShoppingFeed\Manager\Model\Feed\Product\Section\TypePoolInterface as SectionTypePoolInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\Collection as StoreCollection;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\CollectionFactory as StoreCollectionFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\ProductFactory as FeedProductResourceFactory;

class FeedAttributes extends AbstractModifier
{
    const DATA_SCOPE_SFM_MODULE = 'shoppingfeed_manager';

    const GROUP_SHOPPING_FEED_ATTRIBUTES = 'sfm-feed-attributes';

    const FIELDSET_STORE = 'store-%d';

    const FIELD_IS_SELECTED = 'is_selected';
    const FIELD_SELECTED_CATEGORY_ID = 'selected_category_id';

    /**
     * @var LocatorInterface
     */
    private $locator;

    /**
     * @var CategorySelectorInterface
     */
    private $categorySelector;

    /**
     * @var StoreCollectionFactory
     */
    private $storeCollectionFactory;

    /**
     * @var StoreCollection|null
     */
    private $fullStoreCollection = null;

    /**
     * @var FeedProductResourceFactory
     */
    private $feedProductResourceFactory;

    /**
     * @var SectionTypePoolInterface
     */
    private $sectionTypePool;

    /**
     * @param LocatorInterface $locator
     * @param CategorySelectorInterface $categorySelector
     * @param StoreCollectionFactory $storeCollectionFactory
     * @param FeedProductResourceFactory $feedProductResourceFactory
     * @param SectionTypePoolInterface $sectionTypePool
     */
    public function __construct(
        LocatorInterface $locator,
        CategorySelectorInterface $categorySelector,
        StoreCollectionFactory $storeCollectionFactory,
        FeedProductResourceFactory $feedProductResourceFactory,
        SectionTypePoolInterface $sectionTypePool
    ) {
        $this->locator = $locator;
        $this->categorySelector = $categorySelector;
        $this->storeCollectionFactory = $storeCollectionFactory;
        $this->feedProductResourceFactory = $feedProductResourceFactory;
        $this->sectionTypePool = $sectionTypePool;
    }

    /**
     * @return StoreCollection
     */
    private function getFullStoreCollection()
    {
        if (null === $this->fullStoreCollection) {
            $this->fullStoreCollection = $this->storeCollectionFactory->create();
            $this->fullStoreCollection->load();
        }

        return $this->fullStoreCollection;
    }

    public function modifyData(array $data)
    {
        $product = $this->locator->getProduct();
        $productId = $product->getId();
        $feedProductResource = $this->feedProductResourceFactory->create();
        $storeAttributes = $feedProductResource->getProductFeedAttributes($productId);

        /** @var Store $store */
        foreach ($this->getFullStoreCollection() as $store) {
            $storeId = $store->getId();
            $feedAttributes = $storeAttributes[$storeId] ?? [];

            if (isset($feedAttributes['is_selected'])) {
                $feedAttributes['is_selected'] = (int) $feedAttributes['is_selected'];
            }

            $data[$productId][static::DATA_SOURCE_DEFAULT][static::DATA_SCOPE_SFM_MODULE][$storeId] = $feedAttributes;
        }

        return $data;
    }

    /**
     * @param array $meta
     * @return array
     * @throws LocalizedException
     */
    public function modifyMeta(array $meta)
    {
        $storeCollection = $this->getFullStoreCollection();
        $isSingleStoreMode = ($storeCollection->count() === 1);
        $storeFieldsMeta = [];

        /** @var Store $store */
        foreach ($storeCollection as $store) {
            $fieldsetKey = sprintf(self::FIELDSET_STORE, $store->getId());
            $fieldsMeta = $this->getStoreFieldsMeta($store);

            if ($isSingleStoreMode) {
                $storeFieldsMeta[$fieldsetKey] = [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'componentType' => UiContainer::NAME,
                                'label' => null,
                            ],
                        ],
                    ],
                    'children' => $fieldsMeta,
                ];
            } else {
                $storeFieldsMeta[$fieldsetKey] = [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'componentType' => UiFieldset::NAME,
                                'collapsible' => false,
                                'label' => __('Store: %1 - Feed State', $store->getShoppingFeedName()),
                            ],
                        ],
                    ],
                    'children' => $fieldsMeta,
                ];
            }
        }

        $meta[static::GROUP_SHOPPING_FEED_ATTRIBUTES] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => UiFieldset::NAME,
                        'dataScope' => static::DATA_SCOPE_PRODUCT . '.' . static::DATA_SCOPE_SFM_MODULE,
                        'collapsible' => true,
                        'label' => __('Shopping Feed'),
                        'sortOrder' => 1000,
                    ],
                ],
            ],
            'children' => $storeFieldsMeta,
        ];

        return $meta;
    }

    /**
     * @param Store $store
     * @return array
     * @throws LocalizedException
     */
    private function getStoreFieldsMeta(Store $store)
    {
        /** @var CategoriesSectionType $categoriesSection */
        $categoriesSection = $this->sectionTypePool->getTypeByCode(CategoriesSectionType::CODE);
        $categoriesConfig = $categoriesSection->getConfig();

        $selectionBaseIds = $categoriesConfig->getCategorySelectionIds($store);
        $selectionMode = $categoriesConfig->getCategorySelectionMode($store);
        $isSelectionBaseExcluding = CategorySelectorInterface::SELECTION_MODE_EXCLUDE === $selectionMode;

        return [
            static::CONTAINER_PREFIX . static::FIELD_IS_SELECTED => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'componentType' => UiContainer::NAME,
                            'label' => __('Selected'),
                            'dataScope' => $store->getId(),
                            'breakLine' => false,
                            'sortOrder' => 10,
                        ],
                    ],
                ],
                'children' => [
                    static::FIELD_IS_SELECTED => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'componentType' => UiField::NAME,
                                    'formElement' => UiCheckbox::NAME,
                                    'dataType' => UiBoolean::NAME,
                                    'prefer' => 'toggle',
                                    'valueMap' => [ 'true' => 1, 'false' => 0 ],
                                    'label' => __('Selected'),
                                    'sortOrder' => 10,
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            static::CONTAINER_PREFIX . static::FIELD_SELECTED_CATEGORY_ID => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'componentType' => UiContainer::NAME,
                            'component' => 'Magento_Ui/js/form/components/group',
                            'template' => 'ShoppingFeed_Manager/form/group/group',
                            'label' => __('Forced Category'),
                            'dataScope' => $store->getId(),
                            'breakLine' => false,
                            'sortOrder' => 20,
                        ],
                    ],
                ],
                'children' => [
                    static::FIELD_SELECTED_CATEGORY_ID => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'componentType' => UiField::NAME,
                                    'component' => 'ShoppingFeed_Manager/js/form/element/ui-select',
                                    'formElement' => UiSelect::NAME,
                                    'elementTmpl' => 'ShoppingFeed_Manager/form/element/ui-select',
                                    'options' => $this->categorySelector->getStoreCategoryTree($store),
                                    'multiple' => false,
                                    'filterOptions' => true,
                                    'chipsEnabled' => false,
                                    'disableLabel' => true,
                                    'showFilteredQuantity' => false,
                                    'levelsVisibility' => 3,
                                    'closeBtn' => true,
                                    'clearBtn' => true,
                                    'resetBtn' => true,
                                    'selectionBaseValues' => $selectionBaseIds,
                                    'isSelectionBaseExcluding' => $isSelectionBaseExcluding,
                                    'nonSelectionOptionSelectedNote' => __(
                                        'Attention: you have chosen a category which is not currently part of the selection in the feed configuration.'
                                    ),
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
