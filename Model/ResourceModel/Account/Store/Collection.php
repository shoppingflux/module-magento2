<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Account\Store;

use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Account\Store;
use ShoppingFeed\Manager\Model\ResourceModel\AbstractCollection;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store as StoreResource;

/**
 * @method StoreResource getResource()
 */
class Collection extends AbstractCollection
{
    protected $_idFieldName = StoreInterface::STORE_ID;

    protected function _construct()
    {
        $this->_init(Store::class, StoreResource::class);
    }

    /**
     * @param int|int[] $storeIds
     * @return $this
     */
    public function addIdFilter($storeIds)
    {
        $this->addFieldToFilter(StoreInterface::STORE_ID, [ 'in' => $this->prepareIdFilterValue($storeIds) ]);
        return $this;
    }

    public function toOptionArray()
    {
        return $this->_toOptionArray(StoreInterface::STORE_ID, StoreInterface::SHOPPING_FEED_NAME);
    }

    public function toOptionHash()
    {
        return $this->_toOptionHash(StoreInterface::STORE_ID, StoreInterface::SHOPPING_FEED_NAME);
    }
}
