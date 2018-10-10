<?php

namespace ShoppingFeed\Manager\Model\Sales\Order;

use ShoppingFeed\Manager\Model\Account\Store\AbstractConfig as BaseConfig;

abstract class AbstractConfig extends BaseConfig
{
    const SCOPE = 'orders';

    public function getScope()
    {
        return self::SCOPE;
    }
}
