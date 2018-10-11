<?php

namespace ShoppingFeed\Manager\Model\Marketplace\Order;

use Magento\Framework\Model\AbstractModel;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\LogInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Log as LogResource;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Log\Collection as LogCollection;

/**
 * @method LogResource getResource()
 * @method LogCollection getCollection()
 */
class Log extends AbstractModel implements LogInterface
{
    protected $_eventPrefix = 'shoppingfeed_manager_marketplace_order_log';
    protected $_eventObject = 'marketplace_order_log';

    protected function _construct()
    {
        $this->_init(LogResource::class);
    }

    public function getOrderId()
    {
        return (int) $this->getDataByKey(self::ORDER_ID);
    }

    public function getType()
    {
        return trim($this->getDataByKey(self::TYPE));
    }

    public function getMessage()
    {
        return trim($this->getDataByKey(self::MESSAGE));
    }

    public function getDetails()
    {
        return trim($this->getDataByKey(self::DETAILS));
    }

    public function getCreatedAt()
    {
        return $this->getDataByKey(self::CREATED_AT);
    }

    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, (int) $orderId);
    }

    public function setType($type)
    {
        return $this->setData(self::TYPE, trim($type));
    }

    public function setMessage($message)
    {
        return $this->setData(self::MESSAGE, trim($message));
    }

    public function setDetails($details)
    {
        return $this->setData(self::DETAILS, trim($details));
    }

    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}
