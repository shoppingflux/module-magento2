<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Feed\Product;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use ShoppingFeed\Manager\Model\Feed\Product;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product as ProductResource;


/**
 * @method ProductResource getResource()
 */
class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(Product::class, ProductResource::class);
    }
}
