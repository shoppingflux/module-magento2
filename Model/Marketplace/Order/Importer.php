<?php

namespace ShoppingFeed\Manager\Model\Marketplace\Order;

use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface\Proxy as TimezoneProxy;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\AddressInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterfaceFactory;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\AddressInterface as MarketplaceOrderAddressInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\AddressInterfaceFactory;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\ItemInterface as MarketplaceOrderItemInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\ItemInterfaceFactory;
use ShoppingFeed\Manager\Api\Marketplace\OrderRepositoryInterface;
use ShoppingFeed\Manager\Api\Marketplace\Order\AddressRepositoryInterface;
use ShoppingFeed\Manager\Api\Marketplace\Order\ItemRepositoryInterface;
use ShoppingFeed\Manager\DataObjectFactory;
use ShoppingFeed\Manager\Model\Sales\Order\ConfigInterface as OrderConfigInterface;
use ShoppingFeed\Sdk\Api\Order\OrderResource as ApiOrder;
use ShoppingFeed\Sdk\Api\Order\OrderItem as ApiItem;

class Importer
{
    /**
     * @var TimezoneProxy
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
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * @param TimezoneProxy $localeDateProxy
     * @param DataObjectFactory $dataObjectFactory
     * @param OrderConfigInterface $orderGeneralConfig
     * @param OrderInterfaceFactory $orderFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param AddressInterfaceFactory $addressFactory
     * @param AddressRepositoryInterface $addressRepository
     * @param ItemInterfaceFactory $itemFactory
     * @param ItemRepositoryInterface $itemRepository
     * @param TransactionFactory $transactionFactory
     */
    public function __construct(
        TimezoneProxy $localeDateProxy,
        DataObjectFactory $dataObjectFactory,
        OrderConfigInterface $orderGeneralConfig,
        OrderInterfaceFactory $orderFactory,
        OrderRepositoryInterface $orderRepository,
        AddressInterfaceFactory $addressFactory,
        AddressRepositoryInterface $addressRepository,
        ItemInterfaceFactory $itemFactory,
        ItemRepositoryInterface $itemRepository,
        TransactionFactory $transactionFactory
    ) {
        $this->localeDate = $localeDateProxy;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->orderGeneralConfig = $orderGeneralConfig;
        $this->orderFactory = $orderFactory;
        $this->orderRepository = $orderRepository;
        $this->addressFactory = $addressFactory;
        $this->addressRepository = $addressRepository;
        $this->itemFactory = $itemFactory;
        $this->itemRepository = $itemRepository;
        $this->transactionFactory = $transactionFactory;
    }

    /**
     * @param ApiOrder[] $apiOrders
     * @param StoreInterface $store
     * @throws \Exception
     */
    public function importStoreOrders($apiOrders, StoreInterface $store)
    {
        foreach ($apiOrders as $apiOrder) {
            $this->importApiOrder($apiOrder, $store);
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
     * @param StoreInterface $store
     * @throws \Exception
     */
    public function importApiOrder(ApiOrder $apiOrder, StoreInterface $store)
    {
        try {
            $marketplaceOrder = $this->orderRepository->getByShoppingFeedId($apiOrder->getId());
            $this->importExistingApiOrder($apiOrder, $marketplaceOrder, $store);
        } catch (NoSuchEntityException $e) {
            $this->importNewApiOrder($apiOrder, $store);
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
        $this->importApiBaseOrderData($apiOrder, $marketplaceOrder, $store);

        $billingAddress = $this->importApiOrderAddress(
            MarketplaceOrderAddressInterface::TYPE_BILLING,
            $apiOrder->getBillingAddress(),
            $store
        );

        $shippingAddress = $this->importApiOrderAddress(
            MarketplaceOrderAddressInterface::TYPE_SHIPPING,
            $apiOrder->getShippingAddress(),
            $store
        );

        $items = [];

        foreach ($apiOrder->getItems() as $apiItem) {
            $items[] = $this->importApiOrderItem($apiItem, $store);
        }

        $apiOrderData = $apiOrder->toArray();

        if (!empty($apiOrderData['additionalFields']) && is_array($apiOrderData['additionalFields'])) {
            $additionalFields = $this->dataObjectFactory->create();
            $additionalFields->setData($apiOrderData['additionalFields']);
            $marketplaceOrder->setAdditionalFields($additionalFields);
        }

        $transaction = $this->transactionFactory->create();

        $transaction->addCommitCallback(
            function () use ($marketplaceOrder, $billingAddress, $shippingAddress, $items) {
                $this->orderRepository->save($marketplaceOrder);
                $marketplaceOrderId = $marketplaceOrder->getId();

                $this->addressRepository->save($billingAddress->setOrderId($marketplaceOrderId));
                $this->addressRepository->save($shippingAddress->setOrderId($marketplaceOrderId));

                /** @var MarketplaceOrderItemInterface $item */
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

        $marketplaceOrder->setCreatedAt($apiOrder->getCreatedAt());
        $marketplaceOrder->setUpdatedAt($apiOrder->getUpdatedAt());
        $marketplaceOrder->setFetchedAt($this->localeDate->date(null, null, false)->format('Y-m-d H:i:s'));
    }

    /**
     * @param string $type
     * @param array $apiAddressData
     * @param StoreInterface $store
     * @param MarketplaceOrderAddressInterface|null $address
     * @return MarketplaceOrderAddressInterface
     */
    public function importApiOrderAddress(
        $type,
        array $apiAddressData,
        StoreInterface $store,
        AddressInterface $address = null
    ) {
        $streetLines = [
            $apiAddressData['street'] ?? '',
            $apiAddressData['street2'] ?? '',
        ];

        if (null === $address) {
            $address = $this->addressFactory->create();
        }

        $address->setType($type);
        $address->setFirstName($apiAddressData['firstName'] ?? '');
        $address->setLastName($apiAddressData['lastName'] ?? '');
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
     * @param StoreInterface $store
     * @return MarketplaceOrderItemInterface
     */
    public function importApiOrderItem(ApiItem $apiItem, StoreInterface $store)
    {
        $item = $this->itemFactory->create();
        $item->setReference($apiItem->getReference());
        $item->setQuantity($apiItem->getQuantity());
        $item->setPrice($apiItem->getUnitPrice());
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
        if (!$marketplaceOrder->getSalesOrderId()
            && $this->orderGeneralConfig->shouldSyncNonImportedAddresses($store)
        ) {
            $orderId = $marketplaceOrder->getId();

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
        } else {
            $billingAddress = null;
            $shippingAddress = null;
        }

        $marketplaceOrder->setShoppingFeedStatus($apiOrder->getStatus());
        $marketplaceOrder->setUpdatedAt($apiOrder->getUpdatedAt());

        $transaction = $this->transactionFactory->create();

        $transaction->addCommitCallback(
            function () use ($marketplaceOrder, $billingAddress, $shippingAddress) {
                $this->orderRepository->save($marketplaceOrder);

                if (null !== $billingAddress) {
                    $this->addressRepository->save($billingAddress);
                }

                if (null !== $shippingAddress) {
                    $this->addressRepository->save($shippingAddress);
                }
            }
        );

        $transaction->save();
    }
}
