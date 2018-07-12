<?php

namespace ShoppingFeed\Manager\Model\Marketplace\Order;

use Magento\Framework\Model\AbstractModel;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\TicketInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Ticket as TicketResource;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Ticket\Collection as TicketCollection;


/**
 * @method TicketResource getResource()
 * @method TicketCollection getCollection()
 */
class Ticket extends AbstractModel implements TicketInterface
{
    protected $_eventPrefix = 'shoppingfeed_manager_marketplace_order_ticket';
    protected $_eventObject = 'marketplace_order_ticket';

    protected function _construct()
    {
        $this->_init(TicketResource::class);
    }

    public function getShoppingFeedTicketId()
    {
        return (int) $this->getDataByKey(self::SHOPPING_FEED_TICKET_ID);
    }

    public function getOrderId()
    {
        return (int) $this->getDataByKey(self::ORDER_ID);
    }

    public function getAction()
    {
        return trim($this->getDataByKey(self::ACTION));
    }

    public function getStatus()
    {
        return (int) $this->getDataByKey(self::STATUS);
    }

    public function getCreatedAt()
    {
        return $this->getDataByKey(self::CREATED_AT);
    }

    public function setShoppingFeedTicketId($ticketId)
    {
        return $this->setData(self::SHOPPING_FEED_TICKET_ID, (int) $ticketId);
    }

    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, (int) $orderId);
    }

    public function setAction($action)
    {
        return $this->setData(self::ACTION, trim($action));
    }

    public function setStatus($status)
    {
        return $this->setData(self::STATUS, (int) $status);
    }

    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}
