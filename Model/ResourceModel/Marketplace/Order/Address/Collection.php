<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Address;

use ShoppingFeed\Manager\Api\Data\Marketplace\Order\AddressInterface;
use ShoppingFeed\Manager\Model\Marketplace\Order\Address;
use ShoppingFeed\Manager\Model\ResourceModel\AbstractCollection;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Address as AddressResource;

/**
 * @method AddressResource getResource()
 */
class Collection extends AbstractCollection
{
    protected $_idFieldName = AddressInterface::ADDRESS_ID;

    protected function _construct()
    {
        $this->_init(Address::class, AddressResource::class);
    }

    /**
     * @param int|int[] $orderIds
     * @return $this
     */
    public function addOrderIdFilter($orderIds)
    {
        $this->addFieldToFilter(AddressInterface::ORDER_ID, [ 'in' => $this->prepareIdFilterValue($orderIds) ]);
        return $this;
    }

    /**
     * @return AddressInterface[][]
     */
    public function getAddressesByOrderAndType()
    {
        return $this->getGroupedItems([ AddressInterface::ORDER_ID, AddressInterface::TYPE ]);
    }
}
