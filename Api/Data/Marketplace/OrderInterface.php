<?php

namespace ShoppingFeed\Manager\Api\Data\Marketplace;

use ShoppingFeed\Manager\DataObject;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\AddressInterface;

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
    const IS_TEST = 'is_test';
    const IS_FULFILLED = 'is_fulfilled';
    const MARKETPLACE_NAME = 'marketplace_name';
    const SHOPPING_FEED_STATUS = 'shopping_feed_status';
    const CURRENCY_CODE = 'currency_code';
    const PRODUCT_AMOUNT = 'product_amount';
    const SHIPPING_AMOUNT = 'shipping_amount';
    const FEES_AMOUNT = 'fees_amount';
    const TOTAL_AMOUNT = 'total_amount';
    const PAYMENT_METHOD = 'payment_method';
    const SHIPMENT_CARRIER = 'shipment_carrier';
    const LATEST_SHIP_DATE = 'latest_ship_date';
    const ADDITIONAL_FIELDS = 'additional_fields';
    const IMPORT_REMAINING_TRY_COUNT = 'import_remaining_try_count';
    const HAS_NON_NOTIFIABLE_SHIPMENT = 'has_non_notifiable_shipment';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const FETCHED_AT = 'fetched_at';
    const IMPORTED_AT = 'imported_at';
    const ACKNOWLEDGED_AT = 'acknowledged_at';
    /**#@+*/

    const SALES_ENTITY_FIELD_NAME_BUNDLE_ADJUSTMENT = 'sfm_bundle_adjustment';
    const SALES_ENTITY_FIELD_NAME_BASE_BUNDLE_ADJUSTMENT = 'sfm_base_bundle_adjustment';
    const SALES_ENTITY_FIELD_NAME_BUNDLE_ADJUSTMENT_INCL_TAX = 'sfm_bundle_adjustment_incl_tax';
    const SALES_ENTITY_FIELD_NAME_BASE_BUNDLE_ADJUSTMENT_INCL_TAX = 'sfm_base_bundle_adjustment_incl_tax';

    const SALES_ENTITY_FIELD_NAME_MARKETPLACE_FEES_AMOUNT = 'sfm_marketplace_fees_amount';
    const SALES_ENTITY_FIELD_NAME_MARKETPLACE_FEES_BASE_AMOUNT = 'sfm_marketplace_fees_base_amount';

    const STATUS_CREATED = 'created';
    const STATUS_WAITING_STORE_ACCEPTANCE = 'waiting_store_acceptance';
    const STATUS_REFUSED = 'refused';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_WAITING_SHIPMENT = 'waiting_shipment';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_PARTIALLY_SHIPPED = 'partially_shipped';
    const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';

    const ADDITIONAL_FIELD_CART_DISCOUNT_AMOUNT = 'seller_voucher';
    const ADDITIONAL_FIELD_IS_BUSINESS_ORDER = 'is_business_order';
    const ADDITIONAL_FIELD_TAX_IDENTIFICATION_NUMBER = 'buyer_identification_number';
    const ADDITIONAL_FIELD_VAT_ID = 'tax_registration_id';

    const FULFILMENT_TYPE_MARKETPLACE = 'channel';
    const FULFILMENT_TYPE_STORE = 'store';
    const FULFILMENT_TYPE_UNDEFINED = 'undefined';

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
     * @return bool
     */
    public function isTest();

    /**
     * @return bool
     */
    public function isFulfilled();

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
    public function getFeesAmount();

    /**
     * @return float
     */
    public function getTotalAmount();

    /**
     * @return float
     */
    public function getCartDiscountAmount();

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
     * @return \DateTimeInterface|null
     */
    public function getLatestShipDate();

    /**
     * @return DataObject
     */
    public function getAdditionalFields();

    /**
     * @return bool
     */
    public function isBusinessOrder();

    /**
     * @return string|null
     */
    public function getTaxIdentificationNumber();

    /**
     * @return int
     */
    public function getImportRemainingTryCount();

    /**
     * @return bool
     */
    public function getHasNonNotifiableShipment();

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
     * @return AddressInterface[]
     */
    public function getAddresses();

    /**
     * @return AddressInterface|null
     */
    public function getBillingAddress();

    /**
     * @return AddressInterface|null
     */
    public function getShippingAddress();

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
     * @param $isTest
     * @return OrderInterface
     */
    public function setIsTest($isTest);

    /**
     * @param bool $isFulfilled
     * @return OrderInterface
     */
    public function setIsFulfilled($isFulfilled);

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
    public function setFeesAmount($amount);

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
     * @param \DateTimeInterface|null $latestShipDate
     * @return OrderInterface
     */
    public function setLatestShipDate(\DateTimeInterface $latestShipDate = null);

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
     * @param bool $hasNonNotifiableShipment
     * @return OrderInterface
     */
    public function setHasNonNotifiableShipment($hasNonNotifiableShipment);

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

    /**
     * @param AddressInterface $billingAddress
     * @param AddressInterface $shippingAddress
     * @return OrderInterface
     */
    public function setAddresses(AddressInterface $billingAddress, AddressInterface $shippingAddress);
}
