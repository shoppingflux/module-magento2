<?php

namespace ShoppingFeed\Manager\Api\Data\Marketplace;

use ShoppingFeed\Manager\DataObject;

/**
 * @api
 */
interface OrderInterface
{
    /**#@+*/
    const ORDER_ID = 'order_id';
    const STORE_ID = 'store_id';
    const SALES_ORDER_ID = 'sales_order_id';
    const SHOPPING_FEED_ORDER_ID = 'shopping_feed_order_id';
    const MARKETPLACE_ORDER_NUMBER = 'marketplace_order_number';
    const SHOPPING_FEED_MARKETPLACE_ID = 'shopping_feed_marketplace_id';
    const MARKETPLACE_NAME = 'marketplace_name';
    const SHOPPING_FEED_STATUS = 'shopping_feed_status';
    const CURRENCY_CODE = 'currency_code';
    const PRODUCT_AMOUNT = 'product_amount';
    const SHIPPING_AMOUNT = 'shipping_amount';
    const TOTAL_AMOUNT = 'total_amount';
    const PAYMENT_METHOD = 'payment_method';
    const SHIPMENT_CARRIER = 'shipment_carrier';
    const ADDITIONAL_FIELDS = 'additional_fields';
    const IMPORT_REMAINING_TRY_COUNT = 'import_remaining_try_count';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const FETCHED_AT = 'fetched_at';
    const IMPORTED_AT = 'imported_at';
    const ACKNOWLEDGED_AT = 'acknowledged_at';
    /**#@+*/

    const STATUS_CREATED = 'created';
    const STATUS_WAITING_STORE_ACCEPTANCE = 'waiting_store_acceptance';
    const STATUS_REFUSED = 'refused';
    const STATUS_WAITING_SHIPMENT = 'waiting_shipment';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';
    const STATUS_PARTIALLY_SHIPPED = 'partial_shipped';

    const ADDITIONAL_FIELD_IS_BUSINESS_ORDER = 'is_business_order';

    const DEFAULT_IMPORT_TRY_COUNT = 3;

    /**
     * @return int
     */
    public function getId();

    /**
     * @return int
     */
    public function getStoreId();

    /**
     * @return int|null
     */
    public function getSalesOrderId();

    /**
     * @return int
     */
    public function getShoppingFeedOrderId();

    /**
     * @return string
     */
    public function getMarketplaceOrderNumber();

    /**
     * @return int
     */
    public function getShoppingFeedMarketplaceId();

    /**
     * @return string
     */
    public function getMarketplaceName();

    /**
     * @return string
     */
    public function getShoppingFeedStatus();

    /**
     * @return float
     */
    public function getProductAmount();

    /**
     * @return float
     */
    public function getShippingAmount();

    /**
     * @return float
     */
    public function getTotalAmount();

    /**
     * @return string
     */
    public function getCurrencyCode();

    /**
     * @return string
     */
    public function getPaymentMethod();

    /**
     * @return string
     */
    public function getShipmentCarrier();

    /**
     * @return DataObject
     */
    public function getAdditionalFields();

    /**
     * @return bool
     */
    public function isBusinessOrder();

    /**
     * @return int
     */
    public function getImportRemainingTryCount();

    /**
     * @return string
     */
    public function getCreatedAt();

    /**
     * @return string
     */
    public function getUpdatedAt();

    /**
     * @return string
     */
    public function getFetchedAt();

    /**
     * @return string
     */
    public function getImportedAt();

    /**
     * @return string|null
     */
    public function getAcknowledgedAt();

    /**
     * @param int $storeId
     * @return OrderInterface
     */
    public function setStoreId($storeId);

    /**
     * @param int|null $orderId
     * @return OrderInterface
     */
    public function setSalesOrderId($orderId);

    /**
     * @param int $orderId
     * @return OrderInterface
     */
    public function setShoppingFeedOrderId($orderId);

    /**
     * @param string $orderNumber
     * @return OrderInterface
     */
    public function setMarketplaceOrderNumber($orderNumber);

    /**
     * @param int $marketplaceId
     * @return OrderInterface
     */
    public function setShoppingFeedMarketplaceId($marketplaceId);

    /**
     * @param string $marketplaceName
     * @return OrderInterface
     */
    public function setMarketplaceName($marketplaceName);

    /**
     * @param string $status
     * @return OrderInterface
     */
    public function setShoppingFeedStatus($status);

    /**
     * @param float $amount
     * @return OrderInterface
     */
    public function setProductAmount($amount);

    /**
     * @param float $amount
     * @return OrderInterface
     */
    public function setShippingAmount($amount);

    /**
     * @param float $amount
     * @return OrderInterface
     */
    public function setTotalAmount($amount);

    /**
     * @param string $currencyCode
     * @return OrderInterface
     */
    public function setCurrencyCode($currencyCode);

    /**
     * @param string $paymentMethod
     * @return OrderInterface
     */
    public function setPaymentMethod($paymentMethod);

    /**
     * @param string $shipmentCarrier
     * @return OrderInterface
     */
    public function setShipmentCarrier($shipmentCarrier);

    /**
     * @param DataObject $additionalFields
     * @return OrderInterface
     */
    public function setAdditionalFields(DataObject $additionalFields);

    /**
     * @return OrderInterface
     */
    public function resetImportRemainingTryCount();

    /**
     * @param int $tryCount
     * @return OrderInterface
     */
    public function setImportRemainingTryCount($tryCount);

    /**
     * @param string $createdAt
     * @return OrderInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * @param string $updatedAt
     * @return OrderInterface
     */
    public function setUpdatedAt($updatedAt);

    /**
     * @param string $fetchedAt
     * @return OrderInterface
     */
    public function setFetchedAt($fetchedAt);

    /**
     * @param string $importedAt
     * @return OrderInterface
     */
    public function setImportedAt($importedAt);

    /**
     * @param string $acknowledgedAt
     * @return OrderInterface
     */
    public function setAcknowledgedAt($acknowledgedAt);
}
