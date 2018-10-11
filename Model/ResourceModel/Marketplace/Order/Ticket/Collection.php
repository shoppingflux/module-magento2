<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Ticket;

use ShoppingFeed\Manager\Api\Data\Marketplace\Order\TicketInterface;
use ShoppingFeed\Manager\Model\Marketplace\Order\Ticket;
use ShoppingFeed\Manager\Model\ResourceModel\AbstractCollection;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Ticket as TicketResource;

/**
 * @method TicketResource getResource()
 */
class Collection extends AbstractCollection
{
    protected $_idFieldName = TicketInterface::TICKET_ID;

    protected function _construct()
    {
        $this->_init(Ticket::class, TicketResource::class);
    }

    /**
     * @param int|int[] $orderIds
     * @return $this
     */
    public function addOrderIdFilter($orderIds)
    {
        $this->addFieldToFilter(TicketInterface::ORDER_ID, [ 'in' => $this->prepareIdFilterValue($orderIds) ]);
        return $this;
    }

    /**
     * @return TicketInterface[][]
     */
    public function getTicketsByOrder()
    {
        return $this->getGroupedItems([ TicketInterface::ORDER_ID ], true);
    }
}
