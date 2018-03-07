<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Config;

use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractConfig;


class Prices extends AbstractConfig implements PricesInterface
{
    public function getFieldsetLabel()
    {
        return __('Feed - Prices Section');
    }
}
