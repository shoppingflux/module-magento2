<?php

namespace ShoppingFeed\Manager\Model\Feed\Product;

use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;


interface AdapterInterface
{
    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function requiresLoadedProduct(StoreInterface $store);
}
