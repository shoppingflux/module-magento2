<?php

namespace ShoppingFeed\Manager\Model\Feed;

use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Account\Store\ConfigInterface as BaseConfig;


interface ConfigInterface extends BaseConfig
{
    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldUseGzipCompression(StoreInterface $store);
}
