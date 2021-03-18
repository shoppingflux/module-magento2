<?php

namespace ShoppingFeed\Manager\Model\Marketplace\Order;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface as TimezoneInterface;
use \Psr\Log\LoggerInterface;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\AddressInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterfaceFactory;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\AddressInterface as MarketplaceAddressInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\AddressInterfaceFactory;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\ItemInterface as MarketplaceItemInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\ItemInterfaceFactory;
use ShoppingFeed\Manager\Api\Marketplace\OrderRepositoryInterface;
use ShoppingFeed\Manager\Api\Marketplace\Order\AddressRepositoryInterface;
use ShoppingFeed\Manager\Api\Marketplace\Order\ItemRepositoryInterface;
use ShoppingFeed\Manager\DataObject;
use ShoppingFeed\Manager\DataObjectFactory;
use ShoppingFeed\Manager\DB\TransactionFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Item\CollectionFactory as ItemCollectionFactory;
use ShoppingFeed\Manager\Model\Sales\Order\ConfigInterface as OrderConfigInterface;
use ShoppingFeed\Sdk\Api\Order\OrderResource as ApiOrder;
use ShoppingFeed\Sdk\Api\Order\OrderItem as ApiItem;

class Importer
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TimezoneInterface
     */
    private $localeDate;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var OrderConfigInterface
     */
    private $orderGeneralConfig;

    /**
     * @var OrderInterfaceFactory
     */
    private $orderFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var AddressInterfaceFactory
     */
    private $addressFactory;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var ItemInterfaceFactory
     */
    private $itemFactory;

    /**
     * @var ItemRepositoryInterface
     */
    private $itemRepository;

    /**
     * @var ItemCollectionFactory
     */
    private $itemCollectionFactory;

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * @param LoggerInterface $logger
     * @param TimezoneInterface $localeDate
     * @param DataObjectFactory $dataObjectFactory
     * @param OrderConfigInterface $orderGeneralConfig
     * @param OrderInterfaceFactory $orderFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param AddressInterfaceFactory $addressFactory
     * @param AddressRepositoryInterface $addressRepository
     * @param ItemInterfaceFactory $itemFactory
     * @param ItemRepositoryInterface $itemRepository
     * @param ItemCollectionFactory $itemCollectionFactory
     * @param TransactionFactory $transactionFactory
     */
    public function __construct(
        LoggerInterface $logger,
        TimezoneInterface $localeDate,
        DataObjectFactory $dataObjectFactory,
        OrderConfigInterface $orderGeneralConfig,
        OrderInterfaceFactory $orderFactory,
        OrderRepositoryInterface $orderRepository,
        AddressInterfaceFactory $addressFactory,
        AddressRepositoryInterface $addressRepository,
        ItemInterfaceFactory $itemFactory,
        ItemRepositoryInterface $itemRepository,
        ItemCollectionFactory $itemCollectionFactory,
        TransactionFactory $transactionFactory
    ) {
        $this->logger = $logger;
        $this->localeDate = $localeDate;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->orderGeneralConfig = $orderGeneralConfig;
        $this->orderFactory = $orderFactory;
        $this->orderRepository = $orderRepository;
        $this->addressFactory = $addressFactory;
        $this->addressRepository = $addressRepository;
        $this->itemFactory = $itemFactory;
        $this->itemRepository = $itemRepository;
        $this->itemCollectionFactory = $itemCollectionFactory;
        $this->transactionFactory = $transactionFactory;
    }

    /**
     * @param ApiOrder[] $apiOrders
     * @param StoreInterface $store
     * @param bool $updateOnly
     * @throws \Exception
     */
    public function importStoreOrders($apiOrders, StoreInterface $store, $updateOnly = false)
    {
        foreach ($apiOrders as $apiOrder) {
            try {
                $this->importApiOrder($apiOrder, $store, $updateOnly);
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }
    }

    /**
     * @param StoreInterface $store
     * @return string
     */
    public function getDefaultCurrencyCode(StoreInterface $store)
    {
        return $store->getBaseStore()->getCurrentCurrencyCode();
    }

    /**
     * @param StoreInterface $store
     * @return string
     */
    public function getDefaultPaymentMethod(StoreInterface $store)
    {
        return (string) __('Marketplace');
    }

    /**
     * @param StoreInterface $store
     * @return string
     */
    public function getDefaultShippingMethod(StoreInterface $store)
    {
        return (string) __('Marketplace');
    }

    /**
     * @param ApiOrder $apiOrder
     * @return bool
     */
    public function isFulfilledApiOrder(ApiOrder $apiOrder)
    {
        $orderData = $apiOrder->toArray();
        $marketplace = strtolower(trim($apiOrder->getChannel()->getName()));
        $paymentMethod = strtolower(trim($apiOrder->getPaymentInformation()['method'] ?? ''));
        $additionalFields = !is_array($orderData['additionalFields']) ? [] : $orderData['additionalFields'];

        return
            // Amazon
            (('amazon' === $marketplace)
                && ('afn' === $paymentMethod))
            // Cdiscount
            || (('cdiscount' === $marketplace)
                && ('clogistique' === $paymentMethod))
            // ManoMano
            || (in_array($marketplace, [ 'manomanopro', 'monechelle' ])
                && (strtolower(trim($additionalFields['env'] ?? '')) === 'epmm'));
    }

    /**
     * @param ApiOrder $apiOrder
     * @param StoreInterface $store
     * @param bool $updateOnly
     * @throws \Exception
     */
    public function importApiOrder(ApiOrder $apiOrder, StoreInterface $store, $updateOnly)
    {
        try {
            $marketplaceOrder = $this->orderRepository->getByMarketplaceIdAndNumber(
                $apiOrder->getChannel()->getId(),
                $apiOrder->getReference()
            );

            $this->importExistingApiOrder($apiOrder, $marketplaceOrder, $store);
        } catch (NoSuchEntityException $e) {
            if (!$updateOnly) {
                $this->importNewApiOrder($apiOrder, $store);
            }
        }
    }

    /**
     * @param ApiOrder $apiOrder
     * @param StoreInterface $store
     * @throws \Exception
     */
    public function importNewApiOrder(ApiOrder $apiOrder, StoreInterface $store)
    {
        $marketplaceOrder = $this->orderFactory->create();

        $marketplaceOrder->setIsFulfilled($this->isFulfilledApiOrder($apiOrder));

        $this->importApiBaseOrderData($apiOrder, $marketplaceOrder, $store);

        $this->importApiPaymentAndShipmentOrderData($apiOrder, $marketplaceOrder, $store);

        $billingAddress = $this->importApiOrderAddress(
            MarketplaceAddressInterface::TYPE_BILLING,
            $apiOrder->getBillingAddress(),
            $store
        );

        $shippingAddress = $this->importApiOrderAddress(
            MarketplaceAddressInterface::TYPE_SHIPPING,
            $apiOrder->getShippingAddress(),
            $store
        );

        $items = [];
        $referenceAliases = (array) $apiOrder->getItemsReferencesAliases();

        foreach ($apiOrder->getItems() as $apiItem) {
            $items[] = $this->importApiOrderItem($apiItem, $referenceAliases, $store);
        }

        $apiOrderData = $apiOrder->toArray();

        if (!empty($apiOrderData['additionalFields']) && is_array($apiOrderData['additionalFields'])) {
            $additionalFields = $this->dataObjectFactory->create();
            $additionalFields->setData($apiOrderData['additionalFields']);
            $this->importApiAdditionalOrderData($apiOrder, $marketplaceOrder, $additionalFields, $store);
        }

        $transaction = $this->transactionFactory->create();
        $transaction->addModelResource($marketplaceOrder);

        $transaction->addCommitCallback(
            function () use ($marketplaceOrder, $billingAddress, $shippingAddress, $items) {
                $this->orderRepository->save($marketplaceOrder);
                $marketplaceOrderId = $marketplaceOrder->getId();

                $this->addressRepository->save($billingAddress->setOrderId($marketplaceOrderId));
                $this->addressRepository->save($shippingAddress->setOrderId($marketplaceOrderId));

                /** @var MarketplaceItemInterface $item */
                foreach ($items as $item) {
                    $this->itemRepository->save($item->setOrderId($marketplaceOrderId));
                }
            }
        );

        $transaction->save();
    }

    /**
     * @param ApiOrder $apiOrder
     * @param MarketplaceOrderInterface $marketplaceOrder
     * @param StoreInterface $store
     */
    public function importApiBaseOrderData(
        ApiOrder $apiOrder,
        MarketplaceOrderInterface $marketplaceOrder,
        StoreInterface $store
    ) {
        $marketplaceOrder->setStoreId($store->getId());
        $marketplaceOrder->setShoppingFeedOrderId($apiOrder->getId());
        $marketplaceOrder->setMarketplaceOrderNumber($apiOrder->getReference());
        $marketplaceOrder->setImportRemainingTryCount(MarketplaceOrderInterface::DEFAULT_IMPORT_TRY_COUNT);

        $channel = $apiOrder->getChannel();
        $marketplaceOrder->setShoppingFeedMarketplaceId($channel->getId());
        $marketplaceOrder->setMarketplaceName($channel->getName());
        $marketplaceOrder->setShoppingFeedStatus($apiOrder->getStatus());

        $marketplaceOrder->setCreatedAt($apiOrder->getCreatedAt()->format('Y-m-d H:i:s'));
        $marketplaceOrder->setUpdatedAt($apiOrder->getUpdatedAt()->format('Y-m-d H:i:s'));
        $marketplaceOrder->setFetchedAt($this->localeDate->date(null, null, false)->format('Y-m-d H:i:s'));
    }

    /**
     * @param ApiOrder $apiOrder
     * @param MarketplaceOrderInterface $marketplaceOrder
     * @param StoreInterface $store
     */
    public function importApiPaymentAndShipmentOrderData(
        ApiOrder $apiOrder,
        MarketplaceOrderInterface $marketplaceOrder,
        StoreInterface $store
    ) {
        $paymentData = $apiOrder->getPaymentInformation();
        $productAmount = (float) $paymentData['productAmount'] ?? 0.0;
        $shippingAmount = (float) $paymentData['shippingAmount'] ?? 0.0;
        $totalAmount = (float) $paymentData['totalAmount'] ?? ($productAmount + $shippingAmount);

        $marketplaceOrder->setProductAmount($productAmount);
        $marketplaceOrder->setShippingAmount($shippingAmount);
        $marketplaceOrder->setTotalAmount($totalAmount);
        $marketplaceOrder->setCurrencyCode($paymentData['currency'] ?? $this->getDefaultCurrencyCode($store));
        $marketplaceOrder->setPaymentMethod($paymentData['method'] ?? $this->getDefaultPaymentMethod($store));

        $shipmentData = $apiOrder->getShipment();
        $marketplaceOrder->setShipmentCarrier($shipmentData['carrier'] ?? $this->getDefaultShippingMethod($store));
    }

    /**
     * @param ApiOrder $apiOrder
     * @param MarketplaceOrderInterface $marketplaceOrder
     * @param DataObject $additionalFields
     * @param StoreInterface $store
     */
    public function importApiAdditionalOrderData(
        ApiOrder $apiOrder,
        MarketplaceOrderInterface $marketplaceOrder,
        DataObject $additionalFields,
        StoreInterface $store
    ) {
        $feesAmount = 0.0;

        if ($additionalFields->hasData('FRAISTRAITEMENT')) {
            $feesAmount += max(0.0, (float) $additionalFields->getData('FRAISTRAITEMENT'));
        }

        if ($additionalFields->hasData('INTERETBCA')) {
            $feesAmount += max(0.0, (float) $additionalFields->getData('INTERETBCA'));
        }

        $marketplaceOrder->setFeesAmount($feesAmount);
        $marketplaceOrder->setAdditionalFields($additionalFields);
    }

    /**
     * @param string $type
     * @param array $apiAddressData
     * @param StoreInterface $store
     * @param MarketplaceAddressInterface|null $address
     * @return MarketplaceAddressInterface
     */
    public function importApiOrderAddress(
        $type,
        array $apiAddressData,
        StoreInterface $store,
        AddressInterface $address = null
    ) {
        $firstName = trim($apiAddressData['firstName'] ?? '');
        $lastName = trim($apiAddressData['lastName'] ?? '');

        if (('' === $firstName) && $this->orderGeneralConfig->shouldSplitLastNameWhenEmptyFirstName($store)) {
            $nameParts = preg_split('/\s+/', $lastName, 2);

            if (isset($nameParts[1])) {
                $firstName = $nameParts[0];
                $lastName = $nameParts[1];
            }
        }

        $streetLines = [
            $apiAddressData['street'] ?? '',
            $apiAddressData['street2'] ?? '',
        ];

        if (null === $address) {
            $address = $this->addressFactory->create();
        }

        $address->setType($type);
        $address->setFirstName($firstName);
        $address->setLastName($lastName);
        $address->setCompany($apiAddressData['company'] ?? '');
        $address->setStreet(trim(implode("\n", array_map('trim', $streetLines))));
        $address->setPostalCode($apiAddressData['postalCode'] ?? '');
        $address->setCity($apiAddressData['city'] ?? '');
        $address->setCountryCode($apiAddressData['country'] ?? '');
        $address->setPhone($apiAddressData['phone'] ?? '');
        $address->setMobilePhone($apiAddressData['mobilePhone'] ?? '');
        $address->setEmail($apiAddressData['email'] ?? '');
        $address->setMiscData($apiAddressData['other'] ?? '');

        return $address;
    }

    /**
     * @param ApiItem $apiItem
     * @param array $referenceAliases
     * @param StoreInterface $store
     * @param MarketplaceItemInterface|null $item
     * @return MarketplaceItemInterface
     */
    public function importApiOrderItem(
        ApiItem $apiItem,
        array $referenceAliases,
        StoreInterface $store,
        MarketplaceItemInterface $item = null
    ) {
        $item = (null !== $item) ? $item : $this->itemFactory->create();
        $reference = $apiItem->getReference();

        if (isset($referenceAliases[$reference])) {
            $reference = $referenceAliases[$reference];
        }

        $item->setReference($reference);
        $item->setQuantity($apiItem->getQuantity());
        $item->setPrice($apiItem->getUnitPrice());
        $item->setTaxAmount($apiItem->getTaxAmount());

        return $item;
    }

    /**
     * @param ApiOrder $apiOrder
     * @param MarketplaceOrderInterface $marketplaceOrder
     * @param StoreInterface $store
     * @throws \Exception
     */
    public function importExistingApiOrder(
        ApiOrder $apiOrder,
        MarketplaceOrderInterface $marketplaceOrder,
        StoreInterface $store
    ) {
        $orderId = $marketplaceOrder->getId();

        $billingAddress = null;
        $shippingAddress = null;
        $savableItems = [];
        $deletableItems = [];

        if (!$marketplaceOrder->getSalesOrderId()) {
            $this->importApiPaymentAndShipmentOrderData($apiOrder, $marketplaceOrder, $store);

            if ($this->orderGeneralConfig->shouldSyncNonImportedAddresses($store)) {
                try {
                    $billingAddress = $this->addressRepository->getByOrderIdAndType(
                        $orderId,
                        AddressInterface::TYPE_BILLING
                    );
                } catch (NoSuchEntityException $e) {
                    $billingAddress = $this->addressFactory->create();
                    $billingAddress->setOrderId($orderId);
                }

                try {
                    $shippingAddress = $this->addressRepository->getByOrderIdAndType(
                        $marketplaceOrder->getId(),
                        AddressInterface::TYPE_SHIPPING
                    );
                } catch (NoSuchEntityException $e) {
                    $shippingAddress = $this->addressFactory->create();
                    $shippingAddress->setOrderId($orderId);
                }

                $this->importApiOrderAddress(
                    AddressInterface::TYPE_BILLING,
                    $apiOrder->getBillingAddress(),
                    $store,
                    $billingAddress
                );

                $this->importApiOrderAddress(
                    AddressInterface::TYPE_SHIPPING,
                    $apiOrder->getShippingAddress(),
                    $store,
                    $shippingAddress
                );
            }

            if ($this->orderGeneralConfig->shouldSyncNonImportedItems($store)) {
                $itemCollection = $this->itemCollectionFactory->create();
                $itemCollection->addOrderIdFilter($orderId);
                $oldItems = $itemCollection->getItems();
                $referenceAliases = $apiOrder->getItemsReferencesAliases();

                foreach ($apiOrder->getItems() as $apiItem) {
                    $isNewItem = true;
                    $reference = $apiItem->getReference();

                    if (isset($referenceAliases[$reference])) {
                        $reference = $referenceAliases[$reference];
                    }

                    /** @var MarketplaceItemInterface $oldItem */
                    foreach ($oldItems as $key => $oldItem) {
                        if ($oldItem->getReference() === $reference) {
                            $isNewItem = false;
                            $savableItems[] = $this->importApiOrderItem($apiItem, $referenceAliases, $store, $oldItem);
                            unset($oldItems[$key]);
                            break;
                        }
                    }

                    if ($isNewItem) {
                        $savableItems[] = $this->importApiOrderItem($apiItem, $referenceAliases, $store);
                    }
                }

                $deletableItems = $oldItems;
            }
        }

        $marketplaceOrder->setShoppingFeedStatus($apiOrder->getStatus());
        $marketplaceOrder->setUpdatedAt($apiOrder->getUpdatedAt()->format('Y-m-d H:i:s'));

        $transaction = $this->transactionFactory->create();
        $transaction->addModelResource($marketplaceOrder);

        $transaction->addCommitCallback(
            function () use (
                $marketplaceOrder,
                $orderId,
                $billingAddress,
                $shippingAddress,
                $savableItems,
                $deletableItems
            ) {
                $this->orderRepository->save($marketplaceOrder);

                if (null !== $billingAddress) {
                    $this->addressRepository->save($billingAddress);
                }

                if (null !== $shippingAddress) {
                    $this->addressRepository->save($shippingAddress);
                }

                /** @var MarketplaceItemInterface $savableItem */
                foreach ($savableItems as $savableItem) {
                    $savableItem->setOrderId($orderId);
                    $this->itemRepository->save($savableItem);
                }

                foreach ($deletableItems as $deletableItem) {
                    $this->itemRepository->delete($deletableItem);
                }
            }
        );

        $transaction->save();
    }
}
