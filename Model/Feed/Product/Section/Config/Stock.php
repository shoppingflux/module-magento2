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
                        'checkedNotice' => __('The default quantity will be used on products for which stock is not managed.'),
                        'uncheckedNotice' => __('Every product will be assumed to be in stock with the defined default quantity.'),
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
}
