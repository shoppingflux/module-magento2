<?php

namespace ShoppingFeed\Manager\Model\Feed;

use ShoppingFeed\Manager\Model\Account\Store\AbstractConfig as BaseConfig;

abstract class AbstractConfig extends BaseConfig
{
    const SCOPE = 'feed';

    public function getScope()
    {
        return self::SCOPE;
    }
}
