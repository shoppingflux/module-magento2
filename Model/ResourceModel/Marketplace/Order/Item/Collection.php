<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Item;

use ShoppingFeed\Manager\Api\Data\Marketplace\Order\ItemInterface;
use ShoppingFeed\Manager\Model\Marketplace\Order\Item;
use ShoppingFeed\Manager\Model\ResourceModel\AbstractCollection;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Item as ItemResource;


/**
 * @method ItemResource getResource()
 */
class Collection extends AbstractCollection
{
    protected $_idFieldName = ItemInterface::ITEM_ID;

    protected function _construct()
    {
        $this->_init(Item::class, ItemResource::class);
    }

    /**
     * @param int|int[] $orderIds
     * @return $this
     */
    public function addOrderIdFilter($orderIds)
    {
        $this->addFieldToFilter(ItemInterface::ORDER_ID, [ 'in' => $this->prepareIdFilterValue($orderIds) ]);
        return $this;
    }

    /**
     * @return ItemInterface[][]
     */
    public function getItemsByOrder()
    {
        return $this->getGroupedItems([ ItemInterface::ORDER_ID ], true);
    }
}
