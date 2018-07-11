<?php

namespace ShoppingFeed\Manager\Model\Marketplace;

use Magento\Framework\Model\AbstractModel;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order as OrderResource;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Collection as OrderCollection;


/**
 * @method OrderResource getResource()
 * @method OrderCollection getCollection()
 */
class Order extends AbstractModel implements OrderInterface
{
    protected $_eventPrefix = 'shoppingfeed_manager_marketplace_order';
    protected $_eventObject = 'sales_order';

    protected function _construct()
    {
        $this->_init(OrderResource::class);
    }

    public function getId()
    {
        $orderId = (int) parent::getId();
        return empty($orderId) ? null : $orderId;
    }

    public function getStoreId()
    {
        return (int) $this->getDataByKey(self::STORE_ID);
    }

    public function getSalesOrderId()
    {
        return (int) $this->getDataByKey(self::SALES_ORDER_ID);
    }

    public function getShoppingFeedOrderId()
    {
        return (int) $this->getDataByKey(self::SHOPPING_FEED_ORDER_ID);
    }

    public function getMarketplaceOrderNumber()
    {
        return trim($this->getDataByKey(self::MARKETPLACE_ORDER_NUMBER));
    }

    public function getShoppingFeedMarketplaceId()
    {
        return (int) $this->getDataByKey(self::SHOPPING_FEED_MARKETPLACE_ID);
    }

    public function getMarketplaceName()
    {
        return trim($this->getDataByKey(self::MARKETPLACE_NAME));
    }

    public function getShoppingFeedStatus()
    {
        return $this->getDataByKey(self::SHOPPING_FEED_STATUS);
    }

    public function getProductAmount()
    {
        return (float) $this->getDataByKey(self::PRODUCT_AMOUNT);
    }

    public function getShippingAmount()
    {
        return (float) $this->getDataByKey(self::SHIPPING_AMOUNT);
    }

    public function getTotalAmount()
    {
        return (float) $this->getDataByKey(self::TOTAL_AMOUNT);
    }

    public function getCurrencyCode()
    {
        return trim($this->getDataByKey(self::CURRENCY_CODE));
    }

    public function getPaymentMethod()
    {
        return trim($this->getDataByKey(self::PAYMENT_METHOD));
    }

    public function getShipmentCarrier()
    {
        return trim($this->getDataByKey(self::SHIPMENT_CARRIER));
    }

    public function getImportRemainingTryCount()
    {
        return (int) $this->getDataByKey(self::IMPORT_REMAINING_TRY_COUNT);
    }

    public function getCreatedAt()
    {
        return $this->getDataByKey(self::CREATED_AT);
    }

    public function getUpdatedAt()
    {
        return $this->getDataByKey(self::UPDATED_AT);
    }

    public function getFetchedAt()
    {
        return $this->getDataByKey(self::FETCHED_AT);
    }

    public function getImportedAt()
    {
        return $this->getDataByKey(self::IMPORTED_AT);
    }

    public function getAcknowledgedAt()
    {
        return $this->getDataByKey(self::ACKNOWLEDGED_AT);
    }

    public function setStoreId($storeId)
    {
        return $this->setData(self::STORE_ID, (int) $storeId);
    }

    public function setSalesOrderId($orderId)
    {
        return $this->setData(self::SALES_ORDER_ID, (int) $orderId);
    }

    public function setShoppingFeedOrderId($orderId)
    {
        return $this->setData(self::SHOPPING_FEED_ORDER_ID, (int) $orderId);
    }

    public function setMarketplaceOrderNumber($orderNumber)
    {
        return $this->setData(self::MARKETPLACE_ORDER_NUMBER, trim($orderNumber));
    }

    public function setShoppingFeedMarketplaceId($marketplaceId)
    {
        return $this->setData(self::SHOPPING_FEED_MARKETPLACE_ID, (int) $marketplaceId);
    }

    public function setMarketplaceName($marketplaceName)
    {
        return $this->setData(self::MARKETPLACE_NAME, trim($marketplaceName));
    }

    public function setShoppingFeedStatus($status)
    {
        return $this->setData(self::SHOPPING_FEED_STATUS, trim($status));
    }

    public function setProductAmount($amount)
    {
        return $this->setData(self::PRODUCT_AMOUNT, (float) $amount);
    }

    public function setShippingAmount($amount)
    {
        return $this->setData(self::SHIPPING_AMOUNT, (float) $amount);
    }

    public function setTotalAmount($amount)
    {
        return $this->setData(self::TOTAL_AMOUNT, (float) $amount);
    }

    public function setCurrencyCode($currencyCode)
    {
        return $this->setData(self::CURRENCY_CODE, trim($currencyCode));
    }

    public function setPaymentMethod($paymentMethod)
    {
        return $this->setData(self::PAYMENT_METHOD, trim($paymentMethod));
    }

    public function setShipmentCarrier($shipmentCarrier)
    {
        return $this->setData(self::SHIPMENT_CARRIER, trim($shipmentCarrier));
    }

    public function resetImportRemainingTryCount()
    {
        return $this->setImportRemainingTryCount(self::DEFAULT_IMPORT_TRY_COUNT);
    }

    public function setImportRemainingTryCount($tryCount)
    {
        return $this->setData(self::IMPORT_REMAINING_TRY_COUNT, max(0, (int) $tryCount));
    }

    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    public function setFetchedAt($fetchedAt)
    {
        return $this->setData(self::FETCHED_AT, $fetchedAt);
    }

    public function setImportedAt($importedAt)
    {
        return $this->setData(self::IMPORTED_AT, $importedAt);
    }

    public function setAcknowledgedAt($acknowledgedAt)
    {
        return $this->setData(self::ACKNOWLEDGED_AT, $acknowledgedAt);
    }
}
