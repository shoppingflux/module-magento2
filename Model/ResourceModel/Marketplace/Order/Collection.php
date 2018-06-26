<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order;

use ShoppingFeed\Manager\Model\Marketplace\Order;
use ShoppingFeed\Manager\Model\ResourceModel\AbstractCollection;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order as OrderResource;


/**
 * @method OrderResource getResource()
 */
class Collection extends AbstractCollection
{
    protected $_idFieldName = 'order_id';

    protected function _construct()
    {
        $this->_init(Order::class, OrderResource::class);
    }

    /**
     * @param int|int[] $storeIds
     * @return $this
     */
    public function addStoreIdFilter($storeIds)
    {
        $this->addFieldToFilter('store_id', [ 'in' => $this->prepareIdFilterValue($storeIds) ]);
        return $this;
    }

    /**
     * @return $this
     */
    public function addNonImportedFilter()
    {
        $this->addFieldToFilter('sales_order_id', [ 'null' => true ]);
        return $this;
    }
}
