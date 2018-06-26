<?php

namespace ShoppingFeed\Manager\Model\Sales\Order;

use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Account\Store\ConfigInterface as BaseConfig;


interface ConfigInterface extends BaseConfig
{
    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldCreateInvoice(StoreInterface $store);
    
    // @todo use SKUs as product IDs
}
