<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Config;

use Magento\Ui\Component\Form\Element\DataType\Text as UiText;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Config\Field\Checkbox;
use ShoppingFeed\Manager\Model\Config\Field\Hidden;
use ShoppingFeed\Manager\Model\Config\Field\Select;
use ShoppingFeed\Manager\Model\Config\Field\TextBox;
use ShoppingFeed\Manager\Model\Config\FieldFactoryInterface;
use ShoppingFeed\Manager\Model\Config\Value\Handler\PositiveInteger as PositiveIntegerHandler;
use ShoppingFeed\Manager\Model\Config\Value\HandlerFactoryInterface as ValueHandlerFactoryInterface;
use ShoppingFeed\Manager\Model\Config\Value\Handler\Option as OptionHandler;
use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractConfig;
use ShoppingFeed\Manager\Model\Feed\Product\Stock\QtyResolverInterface;

class Stock extends AbstractConfig implements StockInterface
{
    const KEY_USE_ACTUAL_STOCK_STATE = 'use_actual_stock_state';
    const KEY_DEFAULT_QUANTITY = 'default_quantity';
    const KEY_MSI_QUANTITY_TYPE = 'msi_quantity_mode';
    const KEY_FORCE_ZERO_QUANTITY_FOR_NON_SALABLE = 'force_zero_quantity_for_non_salable';
    const KEY_UPDATE_QUANTITY_IN_REAL_TIME = 'update_quantity_in_real_time';

    /**
     * @var QtyResolverInterface
     */
    private $qtyResolver;

    /**
     * @param FieldFactoryInterface $fieldFactory
     * @param ValueHandlerFactoryInterface $valueHandlerFactory
     * @param QtyResolverInterface $qtyResolver
     */
    public function __construct(
        FieldFactoryInterface $fieldFactory,
        ValueHandlerFactoryInterface $valueHandlerFactory,
        QtyResolverInterface $qtyResolver
    ) {
        $this->qtyResolver = $qtyResolver;
        parent::__construct($fieldFactory, $valueHandlerFactory);
    }

    protected function getBaseFields()
    {
        $msiQuantityTypeHandler = $this->valueHandlerFactory->create(
            OptionHandler::TYPE_CODE,
            [
                'dataType' => UiText::NAME,
                'optionArray' => [
                    [
                        'label' => __('Salable'),
                        'value' => QtyResolverInterface::MSI_QUANTITY_TYPE_SALABLE,
                    ],
                    [
                        'label' => __('Stock'),
                        'value' => QtyResolverInterface::MSI_QUANTITY_TYPE_STOCK,
                    ],
                    [
                        'label' => __('Minimum Between Salable and Stock'),
                        'value' => QtyResolverInterface::MSI_QUANTITY_TYPE_MINIMUM,
                    ],
                    [
                        'label' => __('Maximum Between Salable and Stock'),
                        'value' => QtyResolverInterface::MSI_QUANTITY_TYPE_MAXIMUM,
                    ],
                ],
            ]
        );

        $msiQuantityTypeField = $this->fieldFactory->create(
            $this->qtyResolver->isUsingMsi() ? Select::TYPE_CODE : Hidden::TYPE_CODE,
            [
                'name' => self::KEY_MSI_QUANTITY_TYPE,
                'valueHandler' => $msiQuantityTypeHandler,
                'isRequired' => true,
                'defaultFormValue' => QtyResolverInterface::MSI_QUANTITY_TYPE_SALABLE,
                'defaultUseValue' => QtyResolverInterface::MSI_QUANTITY_TYPE_SALABLE,
                'label' => __('Quantity Type'),
                'sortOrder' => 20,
            ]
        );

        return array_merge(
            [
                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_USE_ACTUAL_STOCK_STATE,
                        'isRequired' => true,
                        'isCheckedByDefault' => true,
                        'label' => __('Use Actual Stock State'),
                        'checkedNotice' => __(
                            'The default quantity will be used on products for which stock is not managed.'
                        ),
                        'uncheckedNotice' => __(
                            'Every product will be assumed to be in stock with the defined default quantity.'
                        ),
                        'sortOrder' => 10,
                    ]
                ),

                $msiQuantityTypeField,

                $this->fieldFactory->create(
                    TextBox::TYPE_CODE,
                    [
                        'name' => self::KEY_DEFAULT_QUANTITY,
                        'valueHandler' => $this->valueHandlerFactory->create(PositiveIntegerHandler::TYPE_CODE),
                        'isRequired' => true,
                        'defaultFormValue' => 100,
                        'defaultUseValue' => 100,
                        'label' => __('Default Quantity'),
                        'sortOrder' => 30,
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_FORCE_ZERO_QUANTITY_FOR_NON_SALABLE,
                        'isRequired' => true,
                        'isCheckedByDefault' => false,
                        'label' => __('Force Zero Quantity for Non Salable Products'),
                        'sortOrder' => 40,
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_UPDATE_QUANTITY_IN_REAL_TIME,
                        'isRequired' => true,
                        'isCheckedByDefault' => false,
                        'label' => __('Update Quantities in Real Time'),
                        'uncheckedNotice' => __('Quantities will only be updated through the feed.'),
                        'checkedNotice' => $this->getTranslatedMultiLineString(
                            [
                                'Quantities will also be updated in real-time on Shopping Feed when changes are detected.',
                                'This may slow down product and inventory updates.',
                            ]
                        ),
                        'sortOrder' => 50,
                    ]
                ),
            ],
            parent::getBaseFields()
        );
    }

    public function getFieldsetLabel()
    {
        return __('Feed - Stock Section');
    }

    public function shouldUseActualStockState(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_USE_ACTUAL_STOCK_STATE);
    }

    public function getMsiQuantityType(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_MSI_QUANTITY_TYPE);
    }

    public function getDefaultQuantity(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_DEFAULT_QUANTITY);
    }

    public function shouldForceZeroQuantityForNonSalable(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_FORCE_ZERO_QUANTITY_FOR_NON_SALABLE);
    }

    public function shouldUpdateQuantityInRealTime(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_UPDATE_QUANTITY_IN_REAL_TIME);
    }
}
