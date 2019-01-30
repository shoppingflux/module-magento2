<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Config;

use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Config\Field\Checkbox;
use ShoppingFeed\Manager\Model\Config\Field\TextBox;
use ShoppingFeed\Manager\Model\Config\Value\Handler\PositiveInteger as PositiveIntegerHandler;
use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractConfig;

class Stock extends AbstractConfig implements StockInterface
{
    const KEY_USE_ACTUAL_STOCK_STATE = 'use_actual_stock_state';
    const KEY_DEFAULT_QUANTITY = 'default_quantity';
    const KEY_FORCE_ZERO_QUANTITY_FOR_NON_SALABLE = 'force_zero_quantity_for_non_salable';
    const KEY_UPDATE_QUANTITY_IN_REAL_TIME = 'update_quantity_in_real_time';

    protected function getBaseFields()
    {
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

                $this->fieldFactory->create(
                    TextBox::TYPE_CODE,
                    [
                        'name' => self::KEY_DEFAULT_QUANTITY,
                        'valueHandler' => $this->valueHandlerFactory->create(PositiveIntegerHandler::TYPE_CODE),
                        'isRequired' => true,
                        'defaultFormValue' => 100,
                        'defaultUseValue' => 100,
                        'label' => __('Default Quantity'),
                        'sortOrder' => 20,
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_FORCE_ZERO_QUANTITY_FOR_NON_SALABLE,
                        'isRequired' => true,
                        'isCheckedByDefault' => false,
                        'label' => __('Force Zero Quantity for Non Salable Products'),
                        'sortOrder' => 30,
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_UPDATE_QUANTITY_IN_REAL_TIME,
                        'isRequired' => true,
                        'isCheckedByDefault' => false,
                        'label' => __('Update Quantity in Real Time'),
                        'checkedNotice' => __(
                            'The product quantities will be updated in real-time on Shopping Feed when changes are detected. This may slow down product and inventory updates.'
                        ),
                        'sortOrder' => 40,
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
