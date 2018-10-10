<?php

namespace ShoppingFeed\Manager\Model\Feed\Product;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;

interface AdapterInterface
{
    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function requiresLoadedProduct(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @param ProductCollection $productCollection
     */
    public function prepareLoadableProductCollection(StoreInterface $store, ProductCollection $productCollection);

    /**
     * @param StoreInterface $store
     * @param ProductCollection $productCollection
     */
    public function prepareLoadedProductCollection(StoreInterface $store, ProductCollection $productCollection);
}
