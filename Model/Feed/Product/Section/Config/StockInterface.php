<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Config;

use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\ConfigInterface;


interface StockInterface extends ConfigInterface
{
    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldUseActualStockState(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return int
     */
    public function getDefaultQuantity(StoreInterface $store);
}
