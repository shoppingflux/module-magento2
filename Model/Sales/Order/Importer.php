<?php

namespace ShoppingFeed\Manager\Model\Sales\Order;

use Magento\Catalog\Api\ProductRepositoryInterface\Proxy as CatalogProductRepositoryProxy;
use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Catalog\Model\Product\Type\AbstractType as ProductType;
use Magento\Checkout\Model\Session\Proxy as CheckoutSessionProxy;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartManagementInterface as QuoteManagerInterface;
use Magento\Quote\Api\CartManagementInterface\Proxy as QuoteManagerProxy;
use Magento\Quote\Api\CartRepositoryInterface as QuoteRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface as QuoteAddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\Data\AddressExtensionFactory as QuoteAddressExtensionFactory;
use Magento\Quote\Model\Quote\Address\RateFactory as ShippingAddressRateFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory as ShippingRateMethodFactory;
use Magento\Sales\Api\Data\OrderInterface as SalesOrderInterface;
use Magento\Sales\Model\Order as SalesOrder;
use Magento\Store\Model\Store as BaseStore;
use Magento\Store\Model\StoreManagerInterface as BaseStoreManagerInterface;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\AddressInterface as MarketplaceAddressInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\ItemInterface as MarketplaceItemInterface;
use ShoppingFeed\Manager\Api\Data\Shipping\Method\RuleInterface as ShippingMethodRuleInterface;
use ShoppingFeed\Manager\Api\Marketplace\OrderRepositoryInterface as MarketplaceOrderRepositoryInterface;
use ShoppingFeed\Manager\Model\Marketplace\Order\Manager as MarketplaceOrderManager;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\OrderFactory as MarketplaceOrderResourceFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Address\CollectionFactory as MarketplaceAddressCollectionFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Item\CollectionFactory as MarketplaceItemCollectionFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Shipping\Method\Rule\Collection as ShippingMethodRuleCollection;
use ShoppingFeed\Manager\Model\ResourceModel\Shipping\Method\Rule\CollectionFactory as ShippingMethodRuleCollectionFactory;
use ShoppingFeed\Manager\Model\Sales\Order\ConfigInterface as OrderConfigInterface;
use ShoppingFeed\Manager\Model\Shipping\Method\ApplierPoolInterface as ShippingMethodApplierPoolInterface;
use ShoppingFeed\Manager\Model\TimeHelper;
use ShoppingFeed\Manager\Model\Ui\Payment\ConfigProvider as PaymentConfigProvider;

class Importer implements ImporterInterface
{
    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var TimeHelper
     */
    private $timeHelper;

    /**
     * @var BaseStoreManagerInterface
     */
    private $baseStoreManager;

    /**
     * @var OrderConfigInterface
     */
    private $orderGeneralConfig;

    /**
     * @var CatalogProductRepositoryProxy
     */
    private $catalogProductRepository;

    /**
     * @var CheckoutSessionProxy
     */
    private $checkoutSession;

    /**
     * @var QuoteManagerProxy
     */
    private $quoteManager;

    /**
     * @var QuoteRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var QuoteAddressExtensionFactory
     */
    private $quoteAddressExtensionFactory;

    /**
     * @var ShippingRateMethodFactory
     */
    private $shippingRateMethodFactory;

    /**
     * @var ShippingAddressRateFactory
     */
    private $shippingAddressRateFactory;

    /**
     * @var ShippingMethodApplierPoolInterface
     */
    private $shippingMethodApplierPool;

    /**
     * @var ShippingMethodRuleCollectionFactory
     */
    private $shippingMethodRuleCollectionFactory;

    /**
     * @var ShippingMethodRuleCollection|null
     */
    private $shippingMethodRuleCollection = null;

    /**
     * @var MarketplaceOrderManager
     */
    private $marketplaceOrderManager;

    /**
     * @var MarketplaceOrderRepositoryInterface
     */
    private $marketplaceOrderRepository;

    /**
     * @var MarketplaceOrderResourceFactory
     */
    private $marketplaceOrderResourceFactory;

    /**
     * @var MarketplaceAddressCollectionFactory
     */
    private $marketplaceAddressCollectionFactory;

    /**
     * @var MarketplaceItemCollectionFactory
     */
    private $marketplaceItemCollectionFactory;

    /**
     * @var StoreInterface|null
     */
    private $currentImportStore = null;

    /**
     * @var int|null
     */
    private $currentlyImportedQuoteId = null;

    /**
     * @param TransactionFactory $transactionFactory
     * @param DataObjectFactory $dataObjectFactory
     * @param TimeHelper $timeHelper
     * @param BaseStoreManagerInterface $baseStoreManager
     * @param ConfigInterface $orderGeneralConfig
     * @param CatalogProductRepositoryProxy $catalogProductRepositoryProxy
     * @param CheckoutSessionProxy $checkoutSessionProxy
     * @param QuoteManagerProxy $quoteManagerProxy
     * @param QuoteRepositoryInterface $quoteRepository
     * @param QuoteAddressExtensionFactory $quoteAddressExtensionFactory
     * @param ShippingRateMethodFactory $shippingRateMethodFactory
     * @param ShippingAddressRateFactory $shippingAddressRateFactory
     * @param ShippingMethodApplierPoolInterface $shippingMethodApplierPool
     * @param ShippingMethodRuleCollectionFactory $shippingMethodRuleCollectionFactory
     * @param MarketplaceOrderManager $marketplaceOrderManager
     * @param MarketplaceOrderRepositoryInterface $marketplaceOrderRepository
     * @param MarketplaceOrderResourceFactory $marketplaceOrderResourceFactory
     * @param MarketplaceAddressCollectionFactory $marketplaceAddressCollectionFactory
     * @param MarketplaceItemCollectionFactory $marketplaceItemCollectionFactory
     */
    public function __construct(
        TransactionFactory $transactionFactory,
        DataObjectFactory $dataObjectFactory,
        TimeHelper $timeHelper,
        BaseStoreManagerInterface $baseStoreManager,
        OrderConfigInterface $orderGeneralConfig,
        CatalogProductRepositoryProxy $catalogProductRepositoryProxy,
        CheckoutSessionProxy $checkoutSessionProxy,
        QuoteManagerProxy $quoteManagerProxy,
        QuoteRepositoryInterface $quoteRepository,
        QuoteAddressExtensionFactory $quoteAddressExtensionFactory,
        ShippingRateMethodFactory $shippingRateMethodFactory,
        ShippingAddressRateFactory $shippingAddressRateFactory,
        ShippingMethodApplierPoolInterface $shippingMethodApplierPool,
        ShippingMethodRuleCollectionFactory $shippingMethodRuleCollectionFactory,
        MarketplaceOrderManager $marketplaceOrderManager,
        MarketplaceOrderRepositoryInterface $marketplaceOrderRepository,
        MarketplaceOrderResourceFactory $marketplaceOrderResourceFactory,
        MarketplaceAddressCollectionFactory $marketplaceAddressCollectionFactory,
        MarketplaceItemCollectionFactory $marketplaceItemCollectionFactory
    ) {
        $this->transactionFactory = $transactionFactory;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->timeHelper = $timeHelper;
        $this->baseStoreManager = $baseStoreManager;
        $this->orderGeneralConfig = $orderGeneralConfig;
        $this->catalogProductRepository = $catalogProductRepositoryProxy;
        $this->checkoutSession = $checkoutSessionProxy;
        $this->quoteManager = $quoteManagerProxy;
        $this->quoteRepository = $quoteRepository;
        $this->quoteAddressExtensionFactory = $quoteAddressExtensionFactory;
        $this->shippingRateMethodFactory = $shippingRateMethodFactory;
        $this->shippingAddressRateFactory = $shippingAddressRateFactory;
        $this->shippingMethodApplierPool = $shippingMethodApplierPool;
        $this->shippingMethodRuleCollectionFactory = $shippingMethodRuleCollectionFactory;
        $this->marketplaceOrderManager = $marketplaceOrderManager;
        $this->marketplaceOrderRepository = $marketplaceOrderRepository;
        $this->marketplaceOrderResourceFactory = $marketplaceOrderResourceFactory;
        $this->marketplaceAddressCollectionFactory = $marketplaceAddressCollectionFactory;
        $this->marketplaceItemCollectionFactory = $marketplaceItemCollectionFactory;
    }

    /**
     * @param MarketplaceOrderInterface[] $marketplaceOrders
     * @param StoreInterface $store
     * @throws \Exception
     */
    public function importStoreOrders(array $marketplaceOrders, StoreInterface $store)
    {
        if (null !== $this->currentImportStore) {
            throw new LocalizedException(__('Order import can not be started twice simultaneously.'));
        }

        if (empty($marketplaceOrders)) {
            return;
        }

        $this->currentImportStore = $store;

        /** @var BaseStore $baseStore */
        $baseStore = $store->getBaseStore();
        $orderIds = [];

        foreach ($marketplaceOrders as $marketplaceOrder) {
            $orderIds[] = $marketplaceOrder->getId();
        }

        $orderAddressCollection = $this->marketplaceAddressCollectionFactory->create();
        $orderAddressCollection->addOrderIdFilter($orderIds);
        $orderAddresses = $orderAddressCollection->getAddressesByOrderAndType();

        $orderItemCollection = $this->marketplaceItemCollectionFactory->create();
        $orderItemCollection->addOrderIdFilter($orderIds);
        $orderItems = $orderItemCollection->getItemsByOrder();

        $originalCurrentBaseStore = $this->baseStoreManager->getStore();
        $this->baseStoreManager->setCurrentStore($baseStore);
        $originalBaseStoreCurrencyCode = $baseStore->getCurrentCurrencyCode();

        $marketplaceOrderResource = $this->marketplaceOrderResourceFactory->create();

        try {
            foreach ($marketplaceOrders as $marketplaceOrder) {
                $marketplaceOrderId = $marketplaceOrder->getId();

                try {
                    $baseStore->setCurrentCurrencyCode($marketplaceOrder->getCurrencyCode());
                    $quoteId = (int) $this->quoteManager->createEmptyCart();
                    $this->currentlyImportedQuoteId = $quoteId;

                    /** @var Quote $quote */
                    $quote = $this->quoteRepository->get($quoteId);
                    $quote->setCustomerIsGuest(true);
                    $quote->setCheckoutMethod(QuoteManagerInterface::METHOD_GUEST);
                    $quote->setData(self::QUOTE_KEY_IS_SHOPPING_FEED_ORDER, true);

                    $marketplaceOrderResource->bumpOrderImportTryCount($marketplaceOrderId);
                    $marketplaceOrder->setImportRemainingTryCount($marketplaceOrder->getImportRemainingTryCount() - 1);

                    if (($quoteAddress = $quote->getBillingAddress())
                        && isset($orderAddresses[$marketplaceOrderId][MarketplaceAddressInterface::TYPE_BILLING])
                    ) {
                        $this->importQuoteAddress(
                            $quoteAddress,
                            $orderAddresses[$marketplaceOrderId][MarketplaceAddressInterface::TYPE_BILLING],
                            $store
                        );
                    } else {
                        throw new LocalizedException(__('The marketplace order has no billing address.'));
                    }

                    if (($quoteAddress = $quote->getShippingAddress())
                        && isset($orderAddresses[$marketplaceOrderId][MarketplaceAddressInterface::TYPE_SHIPPING])
                    ) {
                        $this->importQuoteAddress(
                            $quoteAddress,
                            $orderAddresses[$marketplaceOrderId][MarketplaceAddressInterface::TYPE_SHIPPING],
                            $store
                        );
                    } else {
                        throw new LocalizedException(__('The marketplace order has no shipping address.'));
                    }

                    if (isset($orderItems[$marketplaceOrderId])) {
                        $this->importQuoteItems($quote, $orderItems[$marketplaceOrderId], $store);
                    } else {
                        throw new LocalizedException(__('The marketplace order has no item.'));
                    }

                    $this->importQuoteShippingMethod($quote, $marketplaceOrder, $store);
                    $this->importQuotePaymentMethod($quote, $store);

                    $this->quoteRepository->save($quote);
                    $transaction = $this->transactionFactory->create();

                    $transaction->addCommitCallback(
                        function () use ($quoteId, $marketplaceOrder) {
                            $orderId = $this->quoteManager->placeOrder($quoteId);
                            $marketplaceOrder->setSalesOrderId($orderId);
                            $marketplaceOrder->setImportedAt($this->timeHelper->utcDate());
                            $this->marketplaceOrderRepository->save($marketplaceOrder);
                        }
                    );

                    $transaction->save();
                    $salesIncrementId = $this->checkoutSession->getData('last_real_order_id');

                    if (!empty($salesIncrementId)) {
                        try {
                            $this->marketplaceOrderManager->notifyStoreOrderImportSuccess(
                                $marketplaceOrder,
                                $salesIncrementId,
                                $store
                            );
                        } catch (\Exception $e) {
                            // We just want here to acknowledge orders import as soon as possible,
                            // the acknowledgement will automatically be retried later if it did not succeed now.
                        }
                    }
                } catch (\Exception $e) {
                    $this->handleOrderImportException($e, $marketplaceOrder, $store);
                }
            }
        } catch (\Exception $e) {
            throw $e;
        } finally {
            $this->currentImportStore = null;
            $this->currentlyImportedQuoteId = null;
            $baseStore->setCurrentCurrencyCode($originalBaseStoreCurrencyCode);
            $this->baseStoreManager->setCurrentStore($originalCurrentBaseStore);
        }
    }

    /**
     * @param \Exception $importException
     * @param MarketplaceOrderInterface $marketplaceOrder
     * @param StoreInterface $store
     * @throws \Exception
     */
    private function handleOrderImportException(
        \Exception $importException,
        MarketplaceOrderInterface $marketplaceOrder,
        StoreInterface $store
    ) {
        $this->marketplaceOrderManager->logOrderError(
            $marketplaceOrder,
            __('Could not import marketplace order:') . "\n" . $importException->getMessage(),
            (string) $importException
        );

        if ($marketplaceOrder->getImportRemainingTryCount() === 1) {
            $this->marketplaceOrderManager->notifyStoreOrderImportFailure($marketplaceOrder, $store);
        }
    }

    public function importQuoteAddress(
        QuoteAddressInterface $quoteAddress,
        MarketplaceAddressInterface $marketplaceAddress,
        StoreInterface $store
    ) {
        // @todo import rules
        $firstName = $marketplaceAddress->getFirstName();
        $quoteAddress->setFirstname('' !== $firstName ? $firstName : '__');
        $quoteAddress->setLastname($marketplaceAddress->getLastName());
        $quoteAddress->setStreet($marketplaceAddress->getStreet());
        $quoteAddress->setPostcode($marketplaceAddress->getPostalCode());
        $quoteAddress->setCity($marketplaceAddress->getCity());
        $quoteAddress->setCountryId($marketplaceAddress->getCountryCode());
        $quoteAddress->setEmail($marketplaceAddress->getEmail());

        $phone = $marketplaceAddress->getPhone();
        $mobilePhone = $marketplaceAddress->getMobilePhone();

        if (($phone === '')
            || ($this->orderGeneralConfig->shouldUseMobilePhoneNumberFirst($store) && ($mobilePhone !== ''))
        ) {
            $quoteAddress->setTelephone($mobilePhone);
        } else {
            $quoteAddress->setTelephone($phone);
        }

        $this->tagImportedQuoteAddress($quoteAddress);
    }

    /**
     * @param Quote $quote
     * @param MarketplaceItemInterface[] $marketplaceItems
     * @param StoreInterface $store
     * @throws LocalizedException
     */
    public function importQuoteItems(Quote $quote, array $marketplaceItems, StoreInterface $store)
    {
        /** @var MarketplaceItemInterface $marketplaceItem */
        foreach ($marketplaceItems as $marketplaceItem) {
            $reference = $marketplaceItem->getReference();
            $quoteStoreId = $quote->getStoreId();

            try {
                /** @var CatalogProduct $product */

                if ($this->orderGeneralConfig->shouldUseItemReferenceAsProductId($store)) {
                    $product = $this->catalogProductRepository->getById((int) $reference, false, $quoteStoreId, false);
                } else {
                    $product = $this->catalogProductRepository->get($reference, false, $quoteStoreId, false);
                }

                $buyRequest = $this->dataObjectFactory->create(
                    [
                        'data' => [
                            'qty' => $marketplaceItem->getQuantity(),
                            'custom_price' => $marketplaceItem->getPrice(),
                        ],
                    ]
                );

                $product->setData('cart_qty', $marketplaceItem->getQuantity());

                $quote->addProduct(
                    $product,
                    $buyRequest,
                    ProductType::PROCESS_MODE_LITE
                );
            } catch (LocalizedException $e) {
                throw new LocalizedException(
                    __('Could not add the product with reference "%1" to the quote (%2).', $reference, $e->getMessage())
                );
            } catch (\Exception $e) {
                throw new LocalizedException(
                    __('Could not add the product with reference "%1" to the quote (%2).', $reference, (string) $e)
                );
            }
        }
    }

    /**
     * @return ShippingMethodRuleCollection
     */
    private function getShippingMethodRuleCollection()
    {
        if (null === $this->shippingMethodRuleCollection) {
            $this->shippingMethodRuleCollection = $this->shippingMethodRuleCollectionFactory->create();
            $this->shippingMethodRuleCollection->addEnabledAtFilter();
            $this->shippingMethodRuleCollection->addSortOrderOrder();
            $this->shippingMethodRuleCollection->load();
        }

        return $this->shippingMethodRuleCollection;
    }

    /**
     * @param Quote $quote
     * @param MarketplaceOrderInterface $marketplaceOrder
     * @param StoreInterface $store
     * @throws LocalizedException
     */
    public function importQuoteShippingMethod(
        Quote $quote,
        MarketplaceOrderInterface $marketplaceOrder,
        StoreInterface $store
    ) {
        $shippingMethodRuleCollection = $this->getShippingMethodRuleCollection();
        $shippingAddress = $quote->getShippingAddress();
        $shippingRates = $shippingAddress->getAllShippingRates();

        if (empty($shippingRates)) {
            $shippingAddress->setCollectShippingRates(true);
            $shippingAddress->collectShippingRates();
        }

        $shippingAmount = $marketplaceOrder->getShippingAmount();
        $shippingMethodApplierResult = null;

        /** @var ShippingMethodRuleInterface $shippingMethodRule */
        foreach ($shippingMethodRuleCollection as $shippingMethodRule) {
            if ($shippingMethodRule->isAppliableToQuote($quote, $marketplaceOrder)) {
                try {
                    $shippingMethodApplierResult = $this->shippingMethodApplierPool
                        ->getApplierByCode($shippingMethodRule->getApplierCode())
                        ->applyToQuoteShippingAddress(
                            $shippingAddress,
                            $shippingAmount,
                            $shippingMethodRule->getApplierConfiguration()
                        );

                    if (null !== $shippingMethodApplierResult) {
                        break;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        if (null === $shippingMethodApplierResult) {
            $shippingMethodApplierResult = $this->shippingMethodApplierPool
                ->getDefaultApplier()
                ->applyToQuoteShippingAddress($shippingAddress, $shippingAmount, $this->dataObjectFactory->create());
        }

        if (null === $shippingMethodApplierResult) {
            throw new LocalizedException(__('No shipping method could be selected.'));
        }

        $shippingAddress->removeAllShippingRates();
        $rateMethod = $this->shippingRateMethodFactory->create();

        $rateMethod->addData(
            [
                'carrier' => $shippingMethodApplierResult->getCarrierCode(),
                'carrier_title' => $shippingMethodApplierResult->getCarrierTitle(),
                'method' => $shippingMethodApplierResult->getMethodCode(),
                'method_title' => $shippingMethodApplierResult->getMethodTitle(),
                'cost' => $shippingMethodApplierResult->getCost(),
                'price' => $shippingMethodApplierResult->getPrice(),
            ]
        );

        $addressRate = $this->shippingAddressRateFactory->create();
        $addressRate->importShippingRate($rateMethod);
        $shippingAddress->addShippingRate($addressRate);
        $shippingAddress->setShippingMethod($shippingMethodApplierResult->getFullCode());
    }

    /**
     * @param Quote $quote
     * @param StoreInterface $store
     * @throws LocalizedException
     */
    public function importQuotePaymentMethod(Quote $quote, StoreInterface $store)
    {
        $quote->getPayment()->importData([ PaymentInterface::KEY_METHOD => PaymentConfigProvider::CODE ]);
    }

    public function isCurrentlyImportedQuote(Quote $quote)
    {
        return $this->currentlyImportedQuoteId === (int) $quote->getId();
    }

    /**
     * @param QuoteAddressInterface $quoteAddress
     */
    private function tagImportedQuoteAddress(QuoteAddressInterface $quoteAddress)
    {
        if (!$extensionAttributes = $quoteAddress->getExtensionAttributes()) {
            $extensionAttributes = $this->quoteAddressExtensionFactory->create();
        }

        $extensionAttributes->setSfmIsShoppingFeedOrder(true);
        $quoteAddress->setExtensionAttributes($extensionAttributes);
    }

    public function tagImportedQuote(Quote $quote)
    {
        $quote->setData(self::QUOTE_KEY_IS_SHOPPING_FEED_ORDER, true);

        if ($quoteAddress = $quote->getBillingAddress()) {
            $this->tagImportedQuoteAddress($quoteAddress);
        }

        if ($quoteAddress = $quote->getShippingAddress()) {
            $this->tagImportedQuoteAddress($quoteAddress);
        }
    }

    public function isCurrentlyImportedSalesOrder(SalesOrderInterface $order)
    {
        return $this->currentlyImportedQuoteId === (int) $order->getQuoteId();
    }

    /**
     * @param SalesOrderInterface $order
     * @throws LocalizedException
     * @throws \Exception
     */
    public function handleImportedSalesOrder(SalesOrderInterface $order)
    {
        if ((null !== $this->currentImportStore)
            && $this->orderGeneralConfig->shouldCreateInvoice($this->currentImportStore)
            && ($order instanceof SalesOrder)
            && $order->canInvoice()
        ) {
            $invoice = $order->prepareInvoice();
            $invoice->register();
            $transaction = $this->transactionFactory->create();
            $transaction->addObject($invoice);
            $transaction->addObject($order);
            $transaction->save();
        }
    }
}
