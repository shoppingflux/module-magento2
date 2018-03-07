<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Config;

use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Account\Store\Config\Field\Checkbox;
use ShoppingFeed\Manager\Model\Account\Store\Config\Field\TextBox;
use ShoppingFeed\Manager\Model\Account\Store\Config\Value\Handler\PositiveInteger as PositiveIntegerHandler;
use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractConfig;


class Stock extends AbstractConfig implements StockInterface
{
    const KEY_USE_ACTUAL_STOCK_STATE = 'use_actual_stock_state';
    const KEY_DEFAULT_QUANTITY = 'default_quantity';

    protected function getBaseFields()
    {
        return array_merge(
            [
                new Checkbox(
                    self::KEY_USE_ACTUAL_STOCK_STATE,
                    __('Use Actual Stock State'),
                    true,
                    __('The default quantity will be used on products for which stock is not managed.'),
                    __('Every product will be assumed to be in stock with the defined default quantity.')
                ),

                new TextBox(
                    self::KEY_DEFAULT_QUANTITY,
                    new PositiveIntegerHandler(),
                    __('Default Quantity'),
                    true,
                    100,
                    100
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
        return $this->getStoreFieldValue($store, self::KEY_USE_ACTUAL_STOCK_STATE);
    }

    public function getDefaultQuantity(StoreInterface $store)
    {
        return $this->getStoreFieldValue($store, self::KEY_DEFAULT_QUANTITY);
    }
}
