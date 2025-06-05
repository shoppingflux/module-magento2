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

    public function getShoppingFeedBatchId()
    {
        return trim((string) $this->getDataByKey(self::SHOPPING_FEED_BATCH_ID));
    }

    public function getShoppingFeedTicketId()
    {
        return trim((string) $this->getDataByKey(self::SHOPPING_FEED_TICKET_ID));
    }

    public function getOrderId()
    {
        return (int) $this->getDataByKey(self::ORDER_ID);
    }

    public function getSalesEntityId()
    {
        $entityId = $this->getDataByKey(self::SALES_ENTITY_ID);

        return empty($entityId) ? null : (int) $entityId;
    }

    public function getAction()
    {
        return trim((string) $this->getDataByKey(self::ACTION));
    }

    public function getStatus()
    {
        return (int) $this->getDataByKey(self::STATUS);
    }

    public function getCreatedAt()
    {
        return $this->getDataByKey(self::CREATED_AT);
    }

    public function setShoppingFeedBatchId($batchId)
    {
        return $this->setData(self::SHOPPING_FEED_BATCH_ID, trim((string) $batchId));
    }

    public function setShoppingFeedTicketId($ticketId)
    {
        return $this->setData(self::SHOPPING_FEED_TICKET_ID, trim((string) $ticketId));
    }

    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, (int) $orderId);
    }

    public function setSalesEntityId($entityId)
    {
        $entityId = (int) $entityId;

        return $this->setData(self::SALES_ENTITY_ID, empty($entityId) ? null : $entityId);
    }

    public function setAction($action)
    {
        return $this->setData(self::ACTION, trim((string) $action));
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
