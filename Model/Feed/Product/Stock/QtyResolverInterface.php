<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Stock;

use Magento\Catalog\Model\Product as CatalogProduct;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;

interface QtyResolverInterface
{
    const MSI_QUANTITY_TYPE_SALABLE = 'salable';
    const MSI_QUANTITY_TYPE_STOCK = 'stock';
    const MSI_QUANTITY_TYPE_MAXIMUM = 'maximum';
    const MSI_QUANTITY_TYPE_MINIMUM = 'minimum';

    /**
     * @return bool
     */
    public function isUsingMsi();

    /**
     * @param CatalogProduct $product
     * @param StoreInterface $store
     * @param string $msiQuantityType
     * @return float|null
     */
    public function getCatalogProductQuantity(CatalogProduct $product, StoreInterface $store, $msiQuantityType);
}
