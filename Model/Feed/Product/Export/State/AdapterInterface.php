<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Export\State;

use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product\AdapterInterface as BaseAdapter;
use ShoppingFeed\Manager\Model\Feed\RefreshableProduct;

interface AdapterInterface extends BaseAdapter
{
    /**
     * @param StoreInterface $store
     * @param RefreshableProduct $product
     * @return int[]
     */
    public function getProductExportStates(StoreInterface $store, RefreshableProduct $product);
}
