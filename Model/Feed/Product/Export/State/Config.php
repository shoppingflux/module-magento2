<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Export\State;

use Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use Magento\Ui\Component\Form\Element\DataType\Text as UiText;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Config\Field\Checkbox;
use ShoppingFeed\Manager\Model\Config\Field\MultiSelect;
use ShoppingFeed\Manager\Model\Config\Field\Select;
use ShoppingFeed\Manager\Model\Config\Field\TextBox;
use ShoppingFeed\Manager\Model\Config\FieldFactoryInterface;
use ShoppingFeed\Manager\Model\Config\Value\Handler\Option as OptionHandler;
use ShoppingFeed\Manager\Model\Config\Value\Handler\PositiveInteger as PositiveIntegerHandler;
use ShoppingFeed\Manager\Model\Config\Value\HandlerFactoryInterface as ValueHandlerFactoryInterface;
use ShoppingFeed\Manager\Model\Feed\Exporter as FeedExporter;
use ShoppingFeed\Manager\Model\Feed\Product\RefreshableConfig;


class Config extends RefreshableConfig implements ConfigInterface
{
    const SUB_SCOPE = 'export_state';

    const KEY_EXPORT_SELECTED_ONLY = 'export_selected_only';
    const KEY_EXPORTED_VISIBILITIES = 'exported_visibilities';
    const KEY_EXPORT_OUT_OF_STOCK = 'export_out_of_stock';
    const KEY_EXPORT_NOT_SALABLE = 'export_not_salable';
    const KEY_CHILDREN_EXPORT_MODE = 'children_export_mode';
    const KEY_RETAIN_PREVIOUSLY_EXPORTED = 'retain_previously_exported';
    const KEY_PREVIOUSLY_EXPORTED_RETENTION_DURATION = 'previously_exported_retention_duration';

    /**
     * @var ProductVisibility
     */
    private $productVisibility;

    /**
     * @param FieldFactoryInterface $fieldFactory
     * @param ValueHandlerFactoryInterface $valueHandlerFactory
     * @param ProductVisibility $productVisibility
     */
    public function __construct(
        FieldFactoryInterface $fieldFactory,
        ValueHandlerFactoryInterface $valueHandlerFactory,
        ProductVisibility $productVisibility
    ) {
        $this->productVisibility = $productVisibility;
        parent::__construct($fieldFactory, $valueHandlerFactory);
    }

    final public function getScopeSubPath()
    {
        return [ self::SUB_SCOPE ];
    }

    protected function getBaseFields()
    {
        return array_merge(
            [
                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_EXPORT_SELECTED_ONLY,
                        'label' => __('Export Only Selected Products'),
                    ]
                ),

                $this->fieldFactory->create(
                    MultiSelect::TYPE_CODE,
                    [
                        'name' => self::KEY_EXPORTED_VISIBILITIES,
                        'valueHandler' => $this->valueHandlerFactory->create(
                            OptionHandler::TYPE_CODE,
                            [
                                'dataType' => UiText::NAME,
                                'optionArray' => $this->productVisibility->getAllOptions(),
                            ]
                        ),
                        'isRequired' => true,
                        'defaultFormValue' => $this->productVisibility->getVisibleInSiteIds(),
                        'defaultUseValue' => $this->productVisibility->getVisibleInSiteIds(),
                        'size' => 4,
                        'label' => __('Export Products Visible in'),
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_EXPORT_OUT_OF_STOCK,
                        'label' => __('Export Out of Stock Products'),
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_EXPORT_NOT_SALABLE,
                        'label' => __('Export Not Salable Products'),
                    ]
                ),

                $this->fieldFactory->create(
                    Select::TYPE_CODE,
                    [
                        'name' => self::KEY_CHILDREN_EXPORT_MODE,
                        'valueHandler' => $this->valueHandlerFactory->create(
                            OptionHandler::TYPE_CODE,
                            [
                                'dataType' => UiText::NAME,
                                'hasEmptyOption' => true,
                                'optionArray' => [
                                    [
                                        'value' => FeedExporter::CHILDREN_EXPORT_MODE_NONE,
                                        'label' => __('No'),
                                    ],
                                    [
                                        'value' => FeedExporter::CHILDREN_EXPORT_MODE_SEPARATELY,
                                        'label' => __('Separately'),
                                    ],
                                    [
                                        'value' => FeedExporter::CHILDREN_EXPORT_MODE_WITHIN_PARENTS,
                                        'label' => __('Within Parents'),
                                    ],
                                    [
                                        'value' => FeedExporter::CHILDREN_EXPORT_MODE_BOTH,
                                        'label' => __('Separately and Within Parents'),
                                    ],
                                ],
                            ]
                        ),
                        'isRequired' => true,
                        'defaultFormValue' => FeedExporter::CHILDREN_EXPORT_MODE_WITHIN_PARENTS,
                        'defaultUseValue' => FeedExporter::CHILDREN_EXPORT_MODE_WITHIN_PARENTS,
                        'label' => __('Export Child Products'),
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_RETAIN_PREVIOUSLY_EXPORTED,
                        'isRequired' => true,
                        'label' => __('Retain Previously Exported Products'),
                        'checkedDependentFieldNames' => [ self::KEY_PREVIOUSLY_EXPORTED_RETENTION_DURATION ],
                    ]
                ),

                $this->fieldFactory->create(
                    TextBox::TYPE_CODE,
                    [
                        'name' => self::KEY_PREVIOUSLY_EXPORTED_RETENTION_DURATION,
                        'valueHandler' => $this->valueHandlerFactory->create(PositiveIntegerHandler::TYPE_CODE),
                        'isRequired' => true,
                        'defaultFormValue' => 48,
                        'defaultUseValue' => 48,
                        'label' => __('Retention Duration for Previously Exported Products'),
                        'notice' => __('In hours.'),
                    ]
                ),
            ],
            parent::getBaseFields()
        );
    }

    public function getFieldsetLabel()
    {
        return __('Feed - Exportable Products');
    }

    public function shouldExportSelectedOnly(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_EXPORT_SELECTED_ONLY);
    }

    public function getExportedVisibilities(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_EXPORTED_VISIBILITIES);
    }

    public function shouldExportOutOfStock(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_EXPORT_OUT_OF_STOCK);
    }

    public function shouldExportNotSalable(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_EXPORT_NOT_SALABLE);
    }

    public function getChildrenExportMode(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_CHILDREN_EXPORT_MODE);
    }

    public function shouldRetainPreviouslyExported(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_RETAIN_PREVIOUSLY_EXPORTED);
    }

    public function getPreviouslyExportedRetentionDuration(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_PREVIOUSLY_EXPORTED_RETENTION_DURATION) * 3600;
    }
}
