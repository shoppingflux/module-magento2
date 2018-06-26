<?php

namespace ShoppingFeed\Manager\Model\Marketplace\Order;

use Magento\Framework\Model\AbstractModel;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\ItemInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Item as ItemResource;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Item\Collection as ItemCollection;


/**
 * @method ItemResource getResource()
 * @method ItemCollection getCollection()
 */
class Item extends AbstractModel implements ItemInterface
{
    protected $_eventPrefix = 'shoppingfeed_manager_marketplace_order_item';
    protected $_eventObject = 'sales_order_item';

    protected function _construct()
    {
        $this->_init(ItemResource::class);
    }

    public function getOrderId()
    {
        return (int) $this->getDataByKey(self::ORDER_ID);
    }

    public function getReference()
    {
        return trim($this->getDataByKey(self::REFERENCE));
    }

    public function getQuantity()
    {
        return (float) $this->getDataByKey(self::QUANTITY);
    }

    public function getPrice()
    {
        return (float) $this->getDataByKey(self::PRICE);
    }

    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, (int) $orderId);
    }

    public function setReference($reference)
    {
        return $this->setData(self::REFERENCE, trim($reference));
    }

    public function setQuantity($quantity)
    {
        return $this->setData(self::QUANTITY, (float) $quantity);
    }

    public function setPrice($price)
    {
        return $this->setData(self::PRICE, (float) $price);
    }
}
