<?php

namespace ShoppingFeed\Manager\Model\Marketplace\Order;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterfaceFactory;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\AddressInterface as MarketplaceOrderAddressInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\AddressInterfaceFactory;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\ItemInterface as MarketplaceOrderItemInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\ItemInterfaceFactory;
use ShoppingFeed\Manager\Api\Marketplace\OrderRepositoryInterface;
use ShoppingFeed\Manager\Api\Marketplace\Order\AddressRepositoryInterface;
use ShoppingFeed\Manager\Api\Marketplace\Order\ItemRepositoryInterface;
use ShoppingFeed\Manager\Model\ShoppingFeed\Api\SessionManager as ApiSessionManager;
use ShoppingFeed\Sdk\Api\Order\OrderResource as ApiOrder;


class Importer
{
    /**
     * @var ApiSessionManager
     */
    private $apiSessionManager;

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
     * @param ApiSessionManager $apiSessionManager
     * @param OrderInterfaceFactory $orderFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param AddressInterfaceFactory $addressFactory
     * @param AddressRepositoryInterface $addressRepository
     * @param ItemInterfaceFactory $itemFactory
     * @param ItemRepositoryInterface $itemRepository
     */
    public function __construct(
        ApiSessionManager $apiSessionManager,
        OrderInterfaceFactory $orderFactory,
        OrderRepositoryInterface $orderRepository,
        AddressInterfaceFactory $addressFactory,
        AddressRepositoryInterface $addressRepository,
        ItemInterfaceFactory $itemFactory,
        ItemRepositoryInterface $itemRepository
    ) {
        $this->apiSessionManager = $apiSessionManager;
        $this->orderFactory = $orderFactory;
        $this->orderRepository = $orderRepository;
        $this->addressFactory = $addressFactory;
        $this->addressRepository = $addressRepository;
        $this->itemFactory = $itemFactory;
        $this->itemRepository = $itemRepository;
    }

    /**
     * @param StoreInterface $store
     * @throws LocalizedException
     */
    public function importStoreOrders(StoreInterface $store)
    {
        $apiStore = $this->apiSessionManager->getStoreApiResource($store);
        $apiOrders = $apiStore->getOrderApi()->getAll();

        foreach ($apiOrders as $apiOrder) {
            $this->importApiOrder($store, $apiOrder);
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

    public function importApiOrder(StoreInterface $store, ApiOrder $apiOrder)
    {
        try {
            $marketplaceOrder = $this->orderRepository->getByShoppingFeedId($apiOrder->getId());
            $this->importExistingApiOrder($store, $apiOrder, $marketplaceOrder);
        } catch (NoSuchEntityException $e) {
            $this->importNewApiOrder($store, $apiOrder);
        }
    }

    public function importNewApiOrder(StoreInterface $store, ApiOrder $apiOrder)
    {
        $billingAddressData = $apiOrder->getBillingAddress();
        $shippingAddressData = $apiOrder->getShippingAddress();
        $paymentData = $apiOrder->getPaymentInformation();
        $shipmentData = $apiOrder->getShipment();

        $marketplaceOrder = $this->orderFactory->create();
        $marketplaceOrder->setStoreId($store->getId());
        $marketplaceOrder->setShoppingFeedOrderId($apiOrder->getId());
        $marketplaceOrder->setMarketplaceOrderNumber($apiOrder->getReference());
        // @todo in embedded channel
        // $marketplaceOrder->setShoppingFeedMarketplaceId();
        // $marketplaceOrder->setMarketplaceName();
        $marketplaceOrder->setShoppingFeedStatus($apiOrder->getStatus());

        $productAmount = (float) $paymentData['productAmount'] ?? 0.0;
        $shippingAmount = (float) $paymentData['shippingAmount'] ?? 0.0;
        $totalAmount = (float) $paymentData['totalAmount'] ?? ($productAmount + $shippingAmount);
        $marketplaceOrder->setProductAmount($productAmount);
        $marketplaceOrder->setShippingAmount($shippingAmount);
        $marketplaceOrder->setTotalAmount($totalAmount);

        $marketplaceOrder->setCurrencyCode($paymentData['currency'] ?? $this->getDefaultCurrencyCode($store));
        $marketplaceOrder->setPaymentMethod($paymentData['method'] ?? $this->getDefaultPaymentMethod($store));
        $marketplaceOrder->setShipmentCarrier($shipmentData['carrier'] ?? $this->getDefaultShippingMethod($store));

        $billingAddress = $this->importApiOrderAddress(
            $store,
            MarketplaceOrderAddressInterface::TYPE_BILLING,
            $billingAddressData
        );

        $shippingAddress = $this->importApiOrderAddress(
            $store,
            MarketplaceOrderAddressInterface::TYPE_SHIPPING,
            $shippingAddressData
        );

        $items = [];

        foreach ($apiOrder->getItems() as $apiItem) {

        }
    }

    /**
     * @param StoreInterface $store
     * @param string $type
     * @param array $apiAddressData
     * @return MarketplaceOrderAddressInterface
     */
    public function importApiOrderAddress(StoreInterface $store, $type, array $apiAddressData)
    {
        $streetLines = [
            $apiAddressData['street'] ?? '',
            $apiAddressData['street2'] ?? '',
        ];

        $address = $this->addressFactory->create();
        $address->setType($type);
        $address->setFirstName($apiAddressData['first_name'] ?? '');
        $address->setLastName($apiAddressData['last_name'] ?? '');
        $address->setCompany($apiAddressData['company'] ?? '');
        $address->setStreet(trim(implode("\n", array_map('trim', $streetLines))));
        $address->setPostalCode($apiAddressData['postalCode'] ?? '');
        $address->setCity($apiAddressData['postalCode'] ?? '');
        $address->setCountryCode($apiAddressData['country'] ?? '');
        $address->setPhone($apiAddressData['phone'] ?? '');
        $address->setEmail($apiAddressData['email'] ?? '');
        $address->setMiscData($apiAddressData['other'] ?? '');

        return $address;
    }

    /**
     * @param StoreInterface $store
     * @param array $apiItemData
     * @return MarketplaceOrderItemInterface
     */
    public function importApiOrderItem(StoreInterface $store, array $apiItemData)
    {
        $item = $this->itemFactory->create();
        
        
        
        return $item;
    }

    public function importExistingApiOrder(
        StoreInterface $store,
        ApiOrder $apiOrder,
        MarketplaceOrderInterface $marketplaceOrder
    ) {

    }
}
