<?php

namespace ShoppingFeed\Manager\Model\Marketplace;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\AddressInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface;
use ShoppingFeed\Manager\DataObject;
use ShoppingFeed\Manager\DataObjectFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order as OrderResource;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Address\CollectionFactory as AddressCollectionFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Collection as OrderCollection;

/**
 * @method OrderResource getResource()
 * @method OrderCollection getCollection()
 */
class Order extends AbstractModel implements OrderInterface
{
    protected $_eventPrefix = 'shoppingfeed_manager_marketplace_order';
    protected $_eventObject = 'sales_order';

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var AddressCollectionFactory
     */
    private $addressCollectionFactory;

    /**
     * @var AddressInterface[]|null
     */
    private $addresses = null;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param DataObjectFactory $dataObjectFactory
     * @param AddressCollectionFactory $addressCollectionFactory
     * @param OrderResource|null $resource
     * @param OrderCollection|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        DataObjectFactory $dataObjectFactory,
        AddressCollectionFactory $addressCollectionFactory,
        ?OrderResource $resource = null,
        ?OrderCollection $resourceCollection = null,
        array $data = []
    ) {
        $this->dataObjectFactory = $dataObjectFactory;
        $this->addressCollectionFactory = $addressCollectionFactory;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

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
        $orderId = $this->getDataByKey(self::SALES_ORDER_ID);
        return empty($orderId) ? null : (int) $orderId;
    }

    public function getShoppingFeedOrderId()
    {
        return (int) $this->getDataByKey(self::SHOPPING_FEED_ORDER_ID);
    }

    public function getMarketplaceOrderNumber()
    {
        return trim((string) $this->getDataByKey(self::MARKETPLACE_ORDER_NUMBER));
    }

    public function getShoppingFeedMarketplaceId()
    {
        return (int) $this->getDataByKey(self::SHOPPING_FEED_MARKETPLACE_ID);
    }

    public function isFulfilled()
    {
        return (bool) $this->getDataByKey(self::IS_FULFILLED);
    }

    public function isTest()
    {
        return $this->getDataByKey(self::IS_TEST)
            || preg_match('/^TEST-/i', $this->getMarketplaceOrderNumber());
    }

    public function getMarketplaceName()
    {
        return trim((string) $this->getDataByKey(self::MARKETPLACE_NAME));
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

    public function getFeesAmount()
    {
        return (float) $this->getDataByKey(self::FEES_AMOUNT);
    }

    public function getTotalAmount()
    {
        return (float) $this->getDataByKey(self::TOTAL_AMOUNT);
    }

    public function getCartDiscountAmount()
    {
        $amount = (float) $this->getAdditionalFields()
            ->getDataByKey(self::ADDITIONAL_FIELD_CART_DISCOUNT_AMOUNT);

        return ($amount > 0) ? $amount : 0.0;
    }

    public function getCurrencyCode()
    {
        return trim((string) $this->getDataByKey(self::CURRENCY_CODE));
    }

    public function getPaymentMethod()
    {
        return trim((string) $this->getDataByKey(self::PAYMENT_METHOD));
    }

    public function getShipmentCarrier()
    {
        return trim((string) $this->getDataByKey(self::SHIPMENT_CARRIER));
    }

    public function getLatestShipDate()
    {
        $date = trim((string) $this->getDataByKey(self::LATEST_SHIP_DATE));

        return empty($date) ? null : new \DateTimeImmutable($date, new \DateTimeZone('UTC'));
    }

    public function getAdditionalFields()
    {
        $data = $this->getData(self::ADDITIONAL_FIELDS);

        if (!$data instanceof DataObject) {
            $data = is_string($data) ? json_decode($data, true) : [];
            $data = $this->dataObjectFactory->create([ 'data' => is_array($data) ? $data : [] ]);
            $this->setAdditionalFields($data);
        }

        return $data;
    }

    public function isBusinessOrder()
    {
        return (bool) $this->getAdditionalFields()->getData(self::ADDITIONAL_FIELD_IS_BUSINESS_ORDER);
    }

    public function getTaxIdentificationNumber()
    {
        $number = trim(
            (string) $this->getAdditionalFields()
                ->getData(self::ADDITIONAL_FIELD_TAX_IDENTIFICATION_NUMBER)
        );

        return ('' !== $number) ? $number : null;
    }

    public function getImportRemainingTryCount()
    {
        return (int) $this->getDataByKey(self::IMPORT_REMAINING_TRY_COUNT);
    }

    public function getHasNonNotifiableShipment()
    {
        return (bool) $this->getDataByKey(self::HAS_NON_NOTIFIABLE_SHIPMENT);
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

    public function getAddresses()
    {
        if (!is_array($this->addresses)) {
            $addressCollection = $this->addressCollectionFactory->create();
            $addressCollection->addOrderIdFilter($this->getId());
            $this->addresses = $addressCollection->getItems();
        }

        return $this->addresses;
    }

    public function getBillingAddress()
    {
        foreach ($this->getAddresses() as $address) {
            if ($address->getType() === AddressInterface::TYPE_BILLING) {
                return $address;
            }
        }

        return null;
    }

    public function getShippingAddress()
    {
        foreach ($this->getAddresses() as $address) {
            if ($address->getType() === AddressInterface::TYPE_SHIPPING) {
                return $address;
            }
        }

        return null;
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
        return $this->setData(self::MARKETPLACE_ORDER_NUMBER, trim((string) $orderNumber));
    }

    public function setShoppingFeedMarketplaceId($marketplaceId)
    {
        return $this->setData(self::SHOPPING_FEED_MARKETPLACE_ID, (int) $marketplaceId);
    }

    public function setIsTest($isTest)
    {
        return $this->setData(self::IS_TEST, (bool) $isTest);
    }

    public function setIsFulfilled($isFulfilled)
    {
        return $this->setData(self::IS_FULFILLED, (bool) $isFulfilled);
    }

    public function setMarketplaceName($marketplaceName)
    {
        return $this->setData(self::MARKETPLACE_NAME, trim((string) $marketplaceName));
    }

    public function setShoppingFeedStatus($status)
    {
        return $this->setData(self::SHOPPING_FEED_STATUS, trim((string) $status));
    }

    public function setProductAmount($amount)
    {
        return $this->setData(self::PRODUCT_AMOUNT, (float) $amount);
    }

    public function setShippingAmount($amount)
    {
        return $this->setData(self::SHIPPING_AMOUNT, (float) $amount);
    }

    public function setFeesAmount($amount)
    {
        return $this->setData(self::FEES_AMOUNT, (float) $amount);
    }

    public function setTotalAmount($amount)
    {
        return $this->setData(self::TOTAL_AMOUNT, (float) $amount);
    }

    public function setCurrencyCode($currencyCode)
    {
        return $this->setData(self::CURRENCY_CODE, trim((string) $currencyCode));
    }

    public function setPaymentMethod($paymentMethod)
    {
        return $this->setData(self::PAYMENT_METHOD, trim((string) $paymentMethod));
    }

    public function setShipmentCarrier($shipmentCarrier)
    {
        return $this->setData(self::SHIPMENT_CARRIER, trim((string) $shipmentCarrier));
    }

    public function setLatestShipDate(?\DateTimeInterface $latestShipDate = null)
    {
        return $this->setData(
            self::LATEST_SHIP_DATE,
            (null === $latestShipDate) ? null : $latestShipDate->format('Y-m-d')
        );
    }

    public function setAdditionalFields(DataObject $additionalFields)
    {
        return $this->setData(self::ADDITIONAL_FIELDS, $additionalFields);
    }

    public function resetImportRemainingTryCount()
    {
        return $this->setImportRemainingTryCount(self::DEFAULT_IMPORT_TRY_COUNT);
    }

    public function setImportRemainingTryCount($tryCount)
    {
        return $this->setData(self::IMPORT_REMAINING_TRY_COUNT, max(0, (int) $tryCount));
    }

    public function setHasNonNotifiableShipment($hasNonNotifiableShipment)
    {
        return $this->setData(self::HAS_NON_NOTIFIABLE_SHIPMENT, (bool) $hasNonNotifiableShipment);
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

    /**
     * @param AddressInterface $address
     * @param string $addressType
     * @throws LocalizedException
     */
    private function addAddress(AddressInterface $address, $addressType)
    {
        if ($this->getId() !== $address->getOrderId()) {
            throw new LocalizedException(__('The address does not belong to this marketplace order.'));
        }

        if ($address->getType() !== $addressType) {
            throw new LocalizedException(
                __(
                    'The address does not match the expected type (""%1"" instead of ""%2"").',
                    $addressType,
                    $address->getType()
                )
            );
        }

        if (!is_array($this->addresses)) {
            $this->addresses = [];
        }

        $this->addresses[] = $address;
    }

    public function setAddresses(AddressInterface $billingAddress, AddressInterface $shippingAddress)
    {
        $this->addAddress($billingAddress, AddressInterface::TYPE_BILLING);
        $this->addAddress($shippingAddress, AddressInterface::TYPE_SHIPPING);
        return $this;
    }
}
