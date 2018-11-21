<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Config;

use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\ConfigInterface;

interface PricesInterface extends ConfigInterface
{
    const CONFIGURABLE_PRODUCT_PRICE_TYPE_NONE = 'none';
    const CONFIGURABLE_PRODUCT_PRICE_TYPE_VARIATIONS_MINIMUM = 'variations_minimum';
    const CONFIGURABLE_PRODUCT_PRICE_TYPE_VARIATIONS_MAXIMUM = 'variations_maximum';

    /**
     * @param StoreInterface $store
     * @return string
     */
    public function getConfigurableProductPriceType(StoreInterface $store);
}
