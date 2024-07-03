<?php

namespace ShoppingFeed\Manager\Model\Sales\Order;

use Magento\Bundle\Model\Product\Price as BundleProductPrice;
use Magento\Bundle\Model\Product\Type as BundleProductType;
use Magento\Bundle\Model\Selection as BundleProductSelection;
use Magento\Catalog\Api\ProductRepositoryInterface as CatalogProductRepository;
use Magento\Catalog\Helper\Product as CatalogProductHelper;
use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Catalog\Model\Product\Attribute\Source\Status as CatalogProductStatus;
use Magento\Catalog\Model\Product\Type\AbstractType as ProductType;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filter\Template as TemplateFilter;
use Magento\Framework\Registry;
use Magento\Quote\Api\CartManagementInterface as QuoteManager;
use Magento\Quote\Api\CartRepositoryInterface as QuoteRepositoryInterface;
use Magento\Quote\Api\Data\AddressExtensionFactory as QuoteAddressExtensionFactory;
use Magento\Quote\Api\Data\AddressExtensionInterface;
use Magento\Quote\Api\Data\AddressInterface as QuoteAddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\RateFactory as ShippingAddressRateFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory as ShippingRateMethodFactory;
use Magento\Sales\Api\Data\OrderInterface as SalesOrderInterface;
use Magento\Sales\Model\Convert\Order as SalesOrderConverter;
use Magento\Sales\Model\Order as SalesOrder;
use Magento\Sales\Model\Order\ShipmentFactory as SalesShipmentFactory;
use Magento\Store\Model\Store as BaseStore;
use Magento\Store\Model\StoreManagerInterface as BaseStoreManagerInterface;
use Magento\Tax\Model\Config as TaxConfig;
use Magento\Weee\Helper\Data as WeeeHelper;
use Psr\Log\LoggerInterface;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\AddressInterface as MarketplaceAddressInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\ItemInterface as MarketplaceItemInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;
use ShoppingFeed\Manager\Api\Data\Shipping\Method\RuleInterface as ShippingMethodRuleInterface;
use ShoppingFeed\Manager\Api\Marketplace\OrderRepositoryInterface as MarketplaceOrderRepositoryInterface;
use ShoppingFeed\Manager\DB\TransactionFactory;
use ShoppingFeed\Manager\Model\Marketplace\Order\Manager as MarketplaceOrderManager;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Address\CollectionFactory as MarketplaceAddressCollectionFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Item\CollectionFactory as MarketplaceItemCollectionFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\OrderFactory as MarketplaceOrderResourceFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Shipping\Method\Rule\Collection as ShippingMethodRuleCollection;
use ShoppingFeed\Manager\Model\ResourceModel\Shipping\Method\Rule\CollectionFactory as ShippingMethodRuleCollectionFactory;
use ShoppingFeed\Manager\Model\Sales\Order\Business\TaxManager as BusinessTaxManager;
use ShoppingFeed\Manager\Model\Sales\Order\ConfigInterface as OrderConfigInterface;
use ShoppingFeed\Manager\Model\Sales\Order\Customer\Importer as CustomerImporter;
use ShoppingFeed\Manager\Model\Sales\Order\SalesRule\Applier as SalesRuleApplier;
use ShoppingFeed\Manager\Model\Shipping\Method\ApplierPoolInterface as ShippingMethodApplierPoolInterface;
use ShoppingFeed\Manager\Model\TimeHelper;
use ShoppingFeed\Manager\Model\Ui\Payment\ConfigProvider as PaymentConfigProvider;
use ShoppingFeed\Manager\Payment\Gateway\Config\Config as MarketplacePaymentConfig;
use ShoppingFeed\Manager\Plugin\Bundle\Product\PricePlugin as BundleProductPricePlugin;
use ShoppingFeed\Manager\Plugin\Tax\ConfigPlugin as TaxConfigPlugin;
use ShoppingFeed\Manager\Plugin\Weee\TaxPlugin as WeeeTaxPlugin;

class Importer implements ImporterInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

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
     * @var TemplateFilter
     */
    private $templateFilter;

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var BaseStoreManagerInterface
     */
    private $baseStoreManager;

    /**
     * @var OrderConfigInterface
     */
    private $orderGeneralConfig;

    /**
     * @var CatalogProductHelper
     */
    private $catalogProductHelper;

    /**
     * @var CatalogProductRepository
     */
    private $catalogProductRepository;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var QuoteManager
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
     * @var CustomerImporter
     */
    private $customerImporter;

    /**
     * @var BusinessTaxManager
     */
    private $businessTaxManager;

    /**
     * @var TaxConfigPlugin
     */
    private $taxConfigPlugin;

    /**
     * @var WeeeHelper
     */
    private $weeeHelper;

    /**
     * @var WeeeTaxPlugin
     */
    private $weeeTaxPlugin;

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
     * @var SalesOrderConverter
     */
    private $salesOrderConverter;

    /**
     * @var SalesShipmentFactory
     */
    private $salesShipmentFactory;

    /**
     * @var SalesRuleApplier
     */
    private $salesRuleApplier;

    /**
     * @var MarketplacePaymentConfig
     */
    private $marketplacePaymentConfig;

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
     * @var MarketplaceOrderInterface|null
     */
    private $currentlyImportedMarketplaceOrder = null;

    /**
     * @var int|null
     */
    private $currentlyImportedQuoteId = null;

    /**
     * @var float[]|null
     */
    private $currentlyImportedQuoteBundleAdjustments = [];

    /**
     * @var bool
     */
    private $isCurrentlyImportedBusinessQuote = false;

    /**
     * @param LoggerInterface $logger
     * @param TransactionFactory $transactionFactory
     * @param DataObjectFactory $dataObjectFactory
     * @param TimeHelper $timeHelper
     * @param TemplateFilter $templateFilter
     * @param Registry $coreRegistry
     * @param BaseStoreManagerInterface $baseStoreManager
     * @param ConfigInterface $orderGeneralConfig
     * @param CatalogProductHelper $catalogProductHelper
     * @param CatalogProductRepository $catalogProductRepository
     * @param CheckoutSession $checkoutSession
     * @param QuoteManager $quoteManager
     * @param QuoteRepositoryInterface $quoteRepository
     * @param QuoteAddressExtensionFactory $quoteAddressExtensionFactory
     * @param CustomerImporter $customerImporter
     * @param BusinessTaxManager $businessTaxManager
     * @param WeeeHelper $weeeHelper
     * @param WeeeTaxPlugin $weeeTaxPlugin
     * @param TaxConfigPlugin $taxConfigPlugin
     * @param ShippingRateMethodFactory $shippingRateMethodFactory
     * @param ShippingAddressRateFactory $shippingAddressRateFactory
     * @param ShippingMethodApplierPoolInterface $shippingMethodApplierPool
     * @param ShippingMethodRuleCollectionFactory $shippingMethodRuleCollectionFactory
     * @param SalesOrderConverter $salesOrderConverter
     * @param MarketplacePaymentConfig $marketplacePaymentConfig
     * @param MarketplaceOrderManager $marketplaceOrderManager
     * @param MarketplaceOrderRepositoryInterface $marketplaceOrderRepository
     * @param MarketplaceOrderResourceFactory $marketplaceOrderResourceFactory
     * @param MarketplaceAddressCollectionFactory $marketplaceAddressCollectionFactory
     * @param MarketplaceItemCollectionFactory $marketplaceItemCollectionFactory
     * @param SalesShipmentFactory|null $salesShipmentFactory
     */
    public function __construct(
        LoggerInterface $logger,
        TransactionFactory $transactionFactory,
        DataObjectFactory $dataObjectFactory,
        TimeHelper $timeHelper,
        TemplateFilter $templateFilter,
        Registry $coreRegistry,
        BaseStoreManagerInterface $baseStoreManager,
        OrderConfigInterface $orderGeneralConfig,
        CatalogProductHelper $catalogProductHelper,
        CatalogProductRepository $catalogProductRepository,
        CheckoutSession $checkoutSession,
        QuoteManager $quoteManager,
        QuoteRepositoryInterface $quoteRepository,
        QuoteAddressExtensionFactory $quoteAddressExtensionFactory,
        CustomerImporter $customerImporter,
        BusinessTaxManager $businessTaxManager,
        WeeeHelper $weeeHelper,
        WeeeTaxPlugin $weeeTaxPlugin,
        TaxConfigPlugin $taxConfigPlugin,
        ShippingRateMethodFactory $shippingRateMethodFactory,
        ShippingAddressRateFactory $shippingAddressRateFactory,
        ShippingMethodApplierPoolInterface $shippingMethodApplierPool,
        ShippingMethodRuleCollectionFactory $shippingMethodRuleCollectionFactory,
        SalesOrderConverter $salesOrderConverter,
        MarketplacePaymentConfig $marketplacePaymentConfig,
        MarketplaceOrderManager $marketplaceOrderManager,
        MarketplaceOrderRepositoryInterface $marketplaceOrderRepository,
        MarketplaceOrderResourceFactory $marketplaceOrderResourceFactory,
        MarketplaceAddressCollectionFactory $marketplaceAddressCollectionFactory,
        MarketplaceItemCollectionFactory $marketplaceItemCollectionFactory,
        SalesShipmentFactory $salesShipmentFactory = null,
        SalesRuleApplier $salesRuleApplier = null
    ) {
        $this->logger = $logger;
        $this->transactionFactory = $transactionFactory;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->timeHelper = $timeHelper;
        $this->templateFilter = $templateFilter;
        $this->coreRegistry = $coreRegistry;
        $this->baseStoreManager = $baseStoreManager;
        $this->orderGeneralConfig = $orderGeneralConfig;
        $this->catalogProductHelper = $catalogProductHelper;
        $this->catalogProductRepository = $catalogProductRepository;
        $this->checkoutSession = $checkoutSession;
        $this->quoteManager = $quoteManager;
        $this->quoteRepository = $quoteRepository;
        $this->quoteAddressExtensionFactory = $quoteAddressExtensionFactory;
        $this->customerImporter = $customerImporter;
        $this->businessTaxManager = $businessTaxManager;
        $this->weeeHelper = $weeeHelper;
        $this->weeeTaxPlugin = $weeeTaxPlugin;
        $this->taxConfigPlugin = $taxConfigPlugin;
        $this->shippingRateMethodFactory = $shippingRateMethodFactory;
        $this->shippingAddressRateFactory = $shippingAddressRateFactory;
        $this->shippingMethodApplierPool = $shippingMethodApplierPool;
        $this->shippingMethodRuleCollectionFactory = $shippingMethodRuleCollectionFactory;
        $this->salesOrderConverter = $salesOrderConverter;
        $this->marketplacePaymentConfig = $marketplacePaymentConfig;
        $this->marketplaceOrderManager = $marketplaceOrderManager;
        $this->marketplaceOrderRepository = $marketplaceOrderRepository;
        $this->marketplaceOrderResourceFactory = $marketplaceOrderResourceFactory;
        $this->marketplaceAddressCollectionFactory = $marketplaceAddressCollectionFactory;
        $this->marketplaceItemCollectionFactory = $marketplaceItemCollectionFactory;
        $this->salesShipmentFactory = $salesShipmentFactory
            ?? ObjectManager::getInstance()->get(SalesShipmentFactory::class);
        $this->salesRuleApplier = $salesRuleApplier
            ?? ObjectManager::getInstance()->get(SalesRuleApplier::class);
    }

    /**
     * @param string $message
     */
    private function logDebugMessage($message)
    {
        if (
            (null !== $this->currentImportStore)
            && $this->orderGeneralConfig->isDebugModeEnabled($this->currentImportStore)
        ) {
            $this->logger->debug($message);
        }
    }

    /**
     * @param MarketplaceOrderInterface $order
     * @param MarketplaceItemInterface[] $orderItems
     * @return bool
     */
    public function isUntaxedBusinessOrder(MarketplaceOrderInterface $order, array $orderItems)
    {
        if ($order->isBusinessOrder()) {
            $isUntaxed = true;

            foreach ($orderItems as $orderItem) {
                if ($orderItem->getTaxAmount() > 0) {
                    $isUntaxed = false;
                    break;
                }
            }

            return $isUntaxed;
        }

        return false;
    }

    /**
     * @param MarketplaceOrderInterface $order
     * @param StoreInterface $store
     * @return bool
     */
    public function isImportableStoreOrder(MarketplaceOrderInterface $order, StoreInterface $store)
    {
        if (!empty($order->getSalesOrderId())) {
            return false;
        }

        $createdAt = \DateTime::createFromFormat('Y-m-d H:i:s', $order->getCreatedAt());

        if ($createdAt < $this->orderGeneralConfig->getOrderImportFromDate($store)) {
            return false;
        }

        if ($order->isTest()) {
            if (!$this->orderGeneralConfig->shouldImportTestOrders($store)) {
                return false;
            }
        } elseif (!$this->orderGeneralConfig->shouldImportLiveOrders($store)) {
            return false;
        }

        $isShipped = ($order->getShoppingFeedStatus() === MarketplaceOrderInterface::STATUS_SHIPPED);

        if ($order->isFulfilled()) {
            return $isShipped && $this->orderGeneralConfig->shouldImportFulfilledOrders($store);
        }

        if ($isShipped) {
            return $this->orderGeneralConfig->shouldImportShippedOrders($store);
        }

        return ($order->getShoppingFeedStatus() === MarketplaceOrderInterface::STATUS_WAITING_SHIPMENT);
    }

    /**
     * @param MarketplaceOrderInterface[] $marketplaceOrders
     * @param StoreInterface $store
     * @throws \Exception
     */
    public function importStoreOrders(array $marketplaceOrders, StoreInterface $store)
    {
        if ($this->isImportRunning()) {
            throw new LocalizedException(__('Order import can not be started twice simultaneously.'));
        }

        if (empty($marketplaceOrders)) {
            return;
        }

        $this->currentImportStore = $store;

        if ($this->orderGeneralConfig->shouldForceCrossBorderTrade($store)) {
            $this->taxConfigPlugin->enableForcedCrossBorderTrade();
        }

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

        $isAnyOrderDiscounted = false;

        foreach ($marketplaceOrders as $marketplaceOrder) {
            if ($marketplaceOrder->getCartDiscountAmount() > 0) {
                $isAnyOrderDiscounted = true;
                break;
            }
        }

        if ($isAnyOrderDiscounted) {
            $this->salesRuleApplier->setupMarketplaceDiscountRules();
        }

        try {
            foreach ($marketplaceOrders as $marketplaceOrder) {
                if (!$this->isImportableStoreOrder($marketplaceOrder, $store)) {
                    continue;
                }

                $marketplaceOrderId = $marketplaceOrder->getId();

                $this->logDebugMessage(
                    sprintf(
                        'Starting import for marketplace order #%s (%s).',
                        $marketplaceOrder->getMarketplaceOrderNumber(),
                        $marketplaceOrder->getMarketplaceName()
                    )
                );

                try {
                    // Some modules may override the current store between two order imports.
                    $this->baseStoreManager->setCurrentStore($baseStore);

                    $this->currentlyImportedMarketplaceOrder = $marketplaceOrder;

                    $marketplaceOrderResource->bumpOrderImportTryCount($marketplaceOrderId);
                    $marketplaceOrder->setImportRemainingTryCount($marketplaceOrder->getImportRemainingTryCount() - 1);

                    $currencyCode = strtoupper($marketplaceOrder->getCurrencyCode());
                    $baseStore->unsetData('current_currency');
                    $baseStore->setCurrentCurrencyCode($currencyCode);

                    if (strtoupper($baseStore->getCurrentCurrency()->getCode()) !== $currencyCode) {
                        throw new LocalizedException(
                            __(
                                'The "%1" currency is currently unavailable (possible causes: it has not been allowed yet in the system configuration, or its conversion rate to "%2" is unknown).',
                                $currencyCode,
                                $baseStore->getBaseCurrencyCode()
                            )
                        );
                    }

                    $this->logDebugMessage(
                        sprintf(
                            'Forced the "%s" currency on store #%d.',
                            $currencyCode,
                            $baseStore->getId()
                        )
                    );

                    $quoteId = (int) $this->quoteManager->createEmptyCart();
                    $this->currentlyImportedQuoteId = $quoteId;

                    /** @var Quote $quote */
                    $quote = $this->quoteRepository->get($quoteId);

                    if (!$this->orderGeneralConfig->shouldCheckProductAvailabilityAndOptions($store)) {
                        $quote->setIsSuperMode(true);
                        $this->catalogProductHelper->setSkipSaleableCheck(true);
                        $this->logDebugMessage('Product availability and options will be checked.');
                    } else {
                        $quote->setIsSuperMode(false);
                        $this->catalogProductHelper->setSkipSaleableCheck(false);
                        $this->logDebugMessage('Product availability and options will not be checked.');
                    }

                    /**
                     * This is mostly useful when the super mode is enabled, but as old quantities are irrelevant here
                     * anyway, ignoring them is always the best way to go.
                     *
                     * The "ignore_old_qty" flag is required with the super mode because of the changes brought by
                     * this commit: https://github.com/magento/magento2/commit/9addb449f372b66b2b73af6dafcbf1fb1b672f94,
                     * which allows this method to be called even when the "is_super_mode" flag is set:
                     * @see \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator\Initializer\StockItem::initialize()
                     */
                    $quote->setIgnoreOldQty(true);

                    $quote->setData(self::QUOTE_KEY_IS_SHOPPING_FEED_ORDER, true);

                    if (!isset($orderAddresses[$marketplaceOrderId][MarketplaceAddressInterface::TYPE_BILLING])) {
                        throw new LocalizedException(__('The marketplace order has no billing address.'));
                    }

                    if (!isset($orderAddresses[$marketplaceOrderId][MarketplaceAddressInterface::TYPE_SHIPPING])) {
                        throw new LocalizedException(__('The marketplace order has no shipping address.'));
                    }

                    if (isset($orderItems[$marketplaceOrderId])) {
                        $isUntaxedBusinessOrder = $this->isUntaxedBusinessOrder(
                            $marketplaceOrder,
                            $orderItems[$marketplaceOrderId]
                        );
                    } else {
                        throw new LocalizedException(__('The marketplace order has no item.'));
                    }

                    $marketplaceOrder->setAddresses(
                        $orderAddresses[$marketplaceOrderId][MarketplaceAddressInterface::TYPE_BILLING],
                        $orderAddresses[$marketplaceOrderId][MarketplaceAddressInterface::TYPE_SHIPPING]
                    );

                    $this->logDebugMessage('Importing the customer.');

                    $this->customerImporter->importQuoteCustomer(
                        $quote,
                        $marketplaceOrder,
                        $orderAddresses[$marketplaceOrderId][MarketplaceAddressInterface::TYPE_BILLING],
                        $store
                    );

                    $this->logDebugMessage('Importing the billing address.');

                    $this->importQuoteAddress(
                        $quote,
                        $marketplaceOrder,
                        $orderAddresses[$marketplaceOrderId][MarketplaceAddressInterface::TYPE_BILLING],
                        $isUntaxedBusinessOrder,
                        $store
                    );

                    $this->logDebugMessage('Importing the shipping address.');

                    $this->importQuoteAddress(
                        $quote,
                        $marketplaceOrder,
                        $orderAddresses[$marketplaceOrderId][MarketplaceAddressInterface::TYPE_SHIPPING],
                        $isUntaxedBusinessOrder,
                        $store
                    );

                    if ($isUntaxedBusinessOrder) {
                        $this->logDebugMessage('Business order: forcing untaxed context.');
                        $this->isCurrentlyImportedBusinessQuote = true;
                        $quote->setData(self::QUOTE_KEY_IS_SHOPPING_FEED_BUSINESS_ORDER, true);
                        $quote->setCustomerGroupId($this->businessTaxManager->getCustomerGroup()->getId());
                        $quote->setCustomerTaxClassId($this->businessTaxManager->getCustomerTaxClass()->getClassId());
                    } else {
                        $this->isCurrentlyImportedBusinessQuote = false;
                    }

                    $this->logDebugMessage('Importing the items.');

                    $this->importQuoteItems(
                        $quote,
                        $orderItems[$marketplaceOrderId],
                        $isUntaxedBusinessOrder,
                        $store
                    );

                    $this->logDebugMessage('Importing the shipping method.');

                    $this->importQuoteShippingMethod(
                        $quote,
                        $marketplaceOrder,
                        $orderAddresses[$marketplaceOrderId][MarketplaceAddressInterface::TYPE_SHIPPING],
                        $store
                    );

                    $this->logDebugMessage('Importing the payment method.');

                    $this->importQuotePaymentMethod($quote, $marketplaceOrder, $store);

                    $this->logDebugMessage('Placing the order.');

                    $this->quoteRepository->save($quote);
                    $transaction = $this->transactionFactory->create();
                    $transaction->addModelResource($marketplaceOrder);
                    $transaction->addModelResource($quote);

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

                    $this->logDebugMessage(sprintf('The order was successfully imported: #%s.', $salesIncrementId));

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
                } finally {
                    $this->logDebugMessage(
                        sprintf(
                            'Ending import for marketplace order #%s (%s).',
                            $marketplaceOrder->getMarketplaceOrderNumber(),
                            $marketplaceOrder->getMarketplaceName()
                        )
                    );

                    $this->currentlyImportedQuoteBundleAdjustments = [];
                }
            }
        } catch (\Exception $e) {
            throw $e;
        } finally {
            $this->taxConfigPlugin->disableForcedCrossBorderTrade();
            $this->currentImportStore = null;
            $this->currentlyImportedMarketplaceOrder = null;
            $this->currentlyImportedQuoteId = null;
            $this->currentlyImportedQuoteBundleAdjustments = [];
            $this->isCurrentlyImportedBusinessQuote = false;
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

        $this->logDebugMessage(
            __('Could not import marketplace order:') . "\n" . $importException->getMessage()
        );

        if ($marketplaceOrder->getImportRemainingTryCount() === 1) {
            $this->marketplaceOrderManager->notifyStoreOrderImportFailure($marketplaceOrder, $store);
        }
    }

    /**
     * @param float $amount
     * @param StoreInterface $store
     * @return float
     */
    private function applyStoreCurrencyRateToAmount($amount, StoreInterface $store)
    {
        $baseStore = $store->getBaseStore();

        if ($baseStore->getCurrentCurrencyCode() !== $baseStore->getBaseCurrencyCode()) {
            $amount /= $baseStore->getCurrentCurrencyRate();
        }

        return $amount;
    }

    public function importQuoteAddress(
        Quote $quote,
        MarketplaceOrderInterface $marketplaceOrder,
        MarketplaceAddressInterface $marketplaceAddress,
        $isUntaxedBusinessOrder,
        StoreInterface $store
    ) {
        $quoteAddress = $this->customerImporter->importQuoteAddress(
            $quote,
            $marketplaceOrder,
            $marketplaceAddress,
            $store
        );

        $this->tagImportedQuoteAddress($quoteAddress, $isUntaxedBusinessOrder);
    }

    /**
     * @param CatalogProduct $product
     * @param Quote $quote
     * @param bool $isUntaxedBusinessOrder
     * @param StoreInterface $store
     * @return float
     */
    private function getCatalogProductWeeeAmount(
        CatalogProduct $product,
        Quote $quote,
        $isUntaxedBusinessOrder,
        StoreInterface $store
    ) {
        $totalWeeeAmountExclTax = 0.0;
        $totalWeeeAmount = 0.0;
        $totalWeeeTaxAmount = 0.0;
        $isCatalogPriceIncludingTax = (bool) $store->getScopeConfigValue(TaxConfig::CONFIG_XML_PATH_PRICE_INCLUDES_TAX);

        $this->weeeTaxPlugin->resetProductLockedAttributes($product->getId());

        $weeeAttributes = $this->weeeHelper->getProductWeeeAttributes(
            $product,
            $quote->getShippingAddress(),
            $quote->getBillingAddress(),
            $store->getBaseStore()->getWebsiteId(),
            true
        );

        $lockedAttributes = [];

        foreach ($weeeAttributes as $weeeAttribute) {
            $amountExclTax = $weeeAttribute->getAmountExclTax();
            $amount = $weeeAttribute->getAmount();
            $taxAmount = $weeeAttribute->getTaxAmount();

            // The components of the total WEEE amount that will subtracted from the product amount.
            // We must not convert those, because the product amount is expected to be in the store currency.
            $totalWeeeAmountExclTax += $amountExclTax;
            $totalWeeeAmount += $amount;
            $totalWeeeTaxAmount += $taxAmount;

            /** @see \Magento\Weee\Model\Total\Quote\Weee::process() */
            $lockedAttribute = clone $weeeAttribute;

            // The attribute amounts are expected to be in the base currency, so we must convert those.
            if ($isUntaxedBusinessOrder && $isCatalogPriceIncludingTax) {
                $amountExclTax = $this->applyStoreCurrencyRateToAmount($amountExclTax, $store);
                $lockedAttribute->setTaxAmount(0);
                $lockedAttribute->setAmount($amountExclTax);
                $lockedAttribute->setAmountExclTax($amountExclTax);
            } elseif (!$isCatalogPriceIncludingTax) {
                $amountInclTax = $this->applyStoreCurrencyRateToAmount($amount + $taxAmount, $store);
                $lockedAttribute->setTaxAmount(0);
                $lockedAttribute->setAmount($amountInclTax);
                $lockedAttribute->setAmountExclTax($amountInclTax);
            } else {
                $lockedAttribute->setTaxAmount($this->applyStoreCurrencyRateToAmount($taxAmount, $store));
                $lockedAttribute->setAmount($this->applyStoreCurrencyRateToAmount($amount, $store));
                $lockedAttribute->setAmountExclTax($this->applyStoreCurrencyRateToAmount($amountExclTax, $store));
            }

            $lockedAttributes[] = $lockedAttribute;
        }

        $this->weeeTaxPlugin->setProductLockedAttributes($product->getId(), $lockedAttributes);

        if ($isUntaxedBusinessOrder && $isCatalogPriceIncludingTax) {
            return $totalWeeeAmountExclTax;
        } elseif (!$isCatalogPriceIncludingTax) {
            return $totalWeeeAmountExclTax + $totalWeeeTaxAmount;
        }

        return $totalWeeeAmount;
    }

    /**
     * @param float[] $numbers
     * @return float
     */
    private function getKahanSum(array $numbers)
    {
        $sum = 0.0;
        $carry = 0.0;

        foreach ($numbers as $number) {
            $x = $number + $carry;
            $y = $sum + $x;
            $carry = ($y - $sum) - $x;
            $sum = $y;
        }

        return $sum;
    }

    /**
     * @param Quote $quote
     * @param MarketplaceItemInterface $marketplaceItem
     * @param CatalogProduct $product
     * @param bool $isUntaxedBusinessOrder
     * @param bool $isWeeeEnabled
     * @param StoreInterface $store
     * @throws LocalizedException
     */
    public function addNonParentProductToQuote(
        Quote $quote,
        MarketplaceItemInterface $marketplaceItem,
        CatalogProduct $product,
        $isUntaxedBusinessOrder,
        $isWeeeEnabled,
        StoreInterface $store
    ) {
        $itemPrice = $marketplaceItem->getPrice();

        if ($isWeeeEnabled) {
            $itemPrice -= $this->getCatalogProductWeeeAmount($product, $quote, $isUntaxedBusinessOrder, $store);
        }

        $buyRequest = $this->dataObjectFactory->create(
            [
                'data' => [
                    'qty' => $marketplaceItem->getQuantity(),
                    'custom_price' => $itemPrice,
                ],
            ]
        );

        $product->setData('cart_qty', $marketplaceItem->getQuantity());

        if ($isUntaxedBusinessOrder) {
            $product->setData('tax_class_id', $this->businessTaxManager->getProductTaxClass()->getClassId());
        }

        $quote->addProduct(
            $product,
            $buyRequest,
            ProductType::PROCESS_MODE_LITE
        );
    }

    /**
     * @param Quote $quote
     * @param MarketplaceItemInterface $marketplaceItem
     * @param CatalogProduct $product
     * @param bool $isUntaxedBusinessOrder
     * @param bool $isWeeeEnabled
     * @param StoreInterface $store
     * @throws LocalizedException
     */
    public function addBundleProductToQuote(
        Quote $quote,
        MarketplaceItemInterface $marketplaceItem,
        CatalogProduct $product,
        $isUntaxedBusinessOrder,
        $isWeeeEnabled,
        StoreInterface $store
    ) {
        /** @var BundleProductType $bundleType */
        $bundleType = $product->getTypeInstance();
        /** @var BundleProductPrice $bundlePrice */
        $bundlePrice = $product->getPriceModel();

        // Add all the default selections to the quote.

        $bundleOptionIds = $bundleType->getOptionsIds($product);
        $bundleOptions = array_fill_keys($bundleOptionIds, []);

        $selectionCollection = $bundleType->getSelectionsCollection($bundleOptionIds, $product);

        /** @var BundleProductSelection $selection */
        foreach ($selectionCollection as $selection) {
            if ($selection->getIsDefault()) {
                $optionId = $selection->getOptionId();
                $bundleOptions[$optionId][] = $selection->getSelectionId();
            } else {
                $selectionCollection->removeItemByKey($selection->getSelectionId());
            }
        }

        if (empty($bundleOptions)) {
            throw new LocalizedException(
                __(
                    'The bundle product "%s" could not be added to the quote (missing options).',
                    $marketplaceItem->getReference()
                )
            );
        }

        foreach ($bundleOptions as $optionId => $selectionIds) {
            if (count($selectionIds) === 1) {
                $bundleOptions[$optionId] = reset($selectionIds);
            }
        }

        $itemPrice = $marketplaceItem->getPrice();
        $itemQuantity = $marketplaceItem->getQuantity();

        $buyRequest = $this->dataObjectFactory->create(
            [
                'data' => [
                    'qty' => $itemQuantity,
                    'custom_price' => $itemPrice,
                    'bundle_option' => $bundleOptions,
                    'bundle_option_qty' => [],
                ],
            ]
        );

        $product->setData('cart_qty', $itemQuantity);

        if ($isUntaxedBusinessOrder) {
            $product->setData('tax_class_id', $this->businessTaxManager->getProductTaxClass()->getClassId());
        }

        $bundleItem = $quote->addProduct(
            $product,
            $buyRequest,
            ProductType::PROCESS_MODE_FULL
        );

        if (is_string($bundleItem)) {
            throw new \Exception($bundleItem);
        }

        // Check that every selection has been added to the quote, and gather their original prices.

        $isFixedPriceBundle = (int) $product->getPriceType() === BundleProductPrice::PRICE_TYPE_FIXED;

        $selectionItems = [];
        $selectionOriginalPrices = [];

        if ($isFixedPriceBundle && $product->hasData(BundleProductPricePlugin::KEY_ORIGINAL_PRICE_TYPE)) {
            // Temporarily restore the original price type to compute the correct original price for each selection.
            $product->setData(BundleProductPricePlugin::KEY_SKIP_PRICE_TYPE_OVERRIDE, true);
            $product->setPriceType($product->getData(BundleProductPricePlugin::KEY_ORIGINAL_PRICE_TYPE));
        }

        foreach ($bundleItem->getChildren() as $childItem) {
            if (
                ($selectionOption = $childItem->getOptionByCode('selection_id'))
                && ($selectionId = (int) $selectionOption->getValue())
                && ($selection = $selectionCollection->getItemById($selectionId))
            ) {
                $childProduct = $childItem->getProduct();
                $selectionItems[$selectionId] = $childItem;

                $selectionCollection->removeItemByKey($selectionId);

                if ($isUntaxedBusinessOrder) {
                    $childProduct->setData(
                        'tax_class_id',
                        $this->businessTaxManager->getProductTaxClass()->getClassId()
                    );
                }

                $selectionOriginalPrices[$selectionId] = $bundlePrice->getSelectionFinalTotalPrice(
                    $product,
                    $childProduct,
                    $itemQuantity,
                    $selection->getSelectionQty(),
                    true,
                    true
                );
            } else {
                throw new LocalizedException(
                    __(
                        'The bundle product "%s" could not be added to the quote (missing items).',
                        $marketplaceItem->getReference()
                    )
                );
            }
        }

        if ($selectionCollection->count() > 0) {
            throw new LocalizedException(
                __(
                    'The bundle product "%s" could not be added to the quote (missing items).',
                    $marketplaceItem->getReference()
                )
            );
        }

        // Assign a new (proportional) price to each selection.

        $selectionFinalPrices = [];

        $itemPriceComparisonBase = !$isFixedPriceBundle
            ? $this->getKahanSum($selectionOriginalPrices)
            : $bundlePrice->getFinalPrice($itemQuantity, $product);

        $priceMultiplier = empty($itemPriceComparisonBase) ? 0 : $itemPrice / $itemPriceComparisonBase;

        $product->unsetData(BundleProductPricePlugin::KEY_SKIP_PRICE_TYPE_OVERRIDE);

        foreach ($selectionItems as $selectionId => $childItem) {
            // This is the base quantity of the child item (irrespective of the quantity of the parent).
            $childItemQuantity = $childItem->getQty();

            $finalUnitPrice = round(
                $selectionOriginalPrices[$selectionId]
                / $childItemQuantity
                * $priceMultiplier,
                2
            );

            $selectionFinalPrices[] = round($finalUnitPrice * $childItemQuantity, 2);

            if ($isWeeeEnabled && !$isFixedPriceBundle) {
                $finalUnitPrice -= $this->getCatalogProductWeeeAmount(
                    $childItem->getProduct(),
                    $quote,
                    $isUntaxedBusinessOrder,
                    $store
                );
            }

            $childItem->setCustomPrice($finalUnitPrice);
            $childItem->setOriginalCustomPrice($finalUnitPrice);
        }

        // Prevent any rounding problem.

        if ($isFixedPriceBundle) {
            /**
             * Rounding problems are not relevant when the bundle price is "fixed":
             * in this case, selection prices are actually not used when calculating the item price,
             * only the (total) custom price that was provided to @see Quote::addProduct().
             */
            return;
        }

        $itemPriceDifference = round($itemPrice - $this->getKahanSum($selectionFinalPrices), 2);

        if (abs($itemPriceDifference) >= 0.01) {
            $isRoundingFixed = false;

            // Prefer adapting custom prices whenever possible.
            foreach ($selectionItems as $childItem) {
                if ($itemPriceDifference * 100 % $childItem->getQty() === 0) {
                    $finalUnitPrice = $childItem->getCustomPrice() + $itemPriceDifference / $childItem->getQty();

                    if ($finalUnitPrice <= 0.0) {
                        continue;
                    }

                    $isRoundingFixed = true;
                    $childItem->setCustomPrice($finalUnitPrice);
                    $childItem->setOriginalCustomPrice($finalUnitPrice);

                    break;
                }
            }

            if (!$isRoundingFixed) {
                // In last resort, move the adjustment to an additional total on the quote.

                if ($itemPriceDifference < 0) {
                    // Tax is not calculated on negative adjustments: adapt item prices to get a positive adjustment.
                    foreach ($selectionItems as $childItem) {
                        $childItemQuantity = $childItem->getQty();
                        $childItemUnitPrice = $childItem->getCustomPrice();

                        $itemAdjustment = min(
                            max(
                                0.0,
                                $childItemUnitPrice - 0.01
                            ),
                            max(
                                0.01,
                                ceil(abs($itemPriceDifference) / $childItemQuantity * 100.0) / 100.0
                            )
                        );

                        $itemPriceDifference += $itemAdjustment * $childItemQuantity;

                        $childItem->setCustomPrice($childItemUnitPrice - $itemAdjustment);
                        $childItem->setOriginalCustomPrice($childItemUnitPrice - $itemAdjustment);

                        if ($itemPriceDifference >= 0) {
                            break;
                        }
                    }
                }

                $taxClassId = (int) $childItem->getProduct()->getData('tax_class_id');

                $this->currentlyImportedQuoteBundleAdjustments[$taxClassId] =
                    ($this->currentlyImportedQuoteBundleAdjustments[$taxClassId] ?? 0.0)
                    + ($itemPriceDifference * $itemQuantity);
            }
        }
    }

    /**
     * @param Quote $quote
     * @param MarketplaceItemInterface[] $marketplaceItems
     * @param bool $isUntaxedBusinessOrder
     * @param StoreInterface $store
     * @throws LocalizedException
     */
    public function importQuoteItems(
        Quote $quote,
        array $marketplaceItems,
        $isUntaxedBusinessOrder,
        StoreInterface $store
    ) {
        $isWeeeEnabled = $this->weeeHelper->isEnabled($store->getBaseStore());
        $shouldUseItemReferenceAsProductId = $this->orderGeneralConfig->shouldUseItemReferenceAsProductId($store);

        /** @var MarketplaceItemInterface $marketplaceItem */
        foreach ($marketplaceItems as $marketplaceItem) {
            $reference = $marketplaceItem->getReference();
            $quoteStoreId = $quote->getStoreId();

            $this->logDebugMessage(sprintf('Importing the item "%s".', $reference));

            try {
                /** @var CatalogProduct $product */
                $product = null;

                try {
                    if (!$shouldUseItemReferenceAsProductId || !ctype_digit(trim((string) $reference))) {
                        $product = $this->catalogProductRepository->get($reference, false, $quoteStoreId, false);
                    }
                } catch (NoSuchEntityException $e) {
                    if (!$shouldUseItemReferenceAsProductId) {
                        throw $e;
                    }
                }

                if (null === $product) {
                    $product = $this->catalogProductRepository->getById(
                        (int) $reference,
                        false,
                        $quoteStoreId,
                        false
                    );
                }

                if (
                    $this->orderGeneralConfig->shouldCheckProductAvailabilityAndOptions($store)
                    && ((int) $product->getStatus() === CatalogProductStatus::STATUS_DISABLED)
                ) {
                    throw new LocalizedException(__('The product with reference "%1" is disabled.', $reference));
                }

                if (
                    $this->orderGeneralConfig->shouldCheckProductWebsites($store)
                    && !in_array($store->getBaseWebsiteId(), array_map('intval', $product->getWebsiteIds()), true)
                ) {
                    throw new LocalizedException(
                        __('The product with reference "%1" is not available in the website.', $reference)
                    );
                }

                if ($product->getTypeId() === BundleProductType::TYPE_CODE) {
                    $this->addBundleProductToQuote(
                        $quote,
                        $marketplaceItem,
                        $product,
                        $isUntaxedBusinessOrder,
                        $isWeeeEnabled,
                        $store
                    );
                } else {
                    $this->addNonParentProductToQuote(
                        $quote,
                        $marketplaceItem,
                        $product,
                        $isUntaxedBusinessOrder,
                        $isWeeeEnabled,
                        $store
                    );
                }
            } catch (LocalizedException $e) {
                throw new LocalizedException(
                    __(
                        'Could not add the product with reference "%1" to the quote (%2).',
                        $reference,
                        $e->getMessage()
                    ),
                    $e
                );
            } catch (\Exception $e) {
                throw new LocalizedException(
                    __('Could not add the product with reference "%1" to the quote (%2).', $reference, (string) $e),
                    $e
                );
            }
        }

        $this->applyBundleAdjustmentsOnQuote($quote);
    }

    /**
     * @return ShippingMethodRuleCollection
     */
    private function getShippingMethodRuleCollection()
    {
        if (null === $this->shippingMethodRuleCollection) {
            $this->shippingMethodRuleCollection = $this->shippingMethodRuleCollectionFactory->create();
            $this->shippingMethodRuleCollection->addActiveFilter();
            $this->shippingMethodRuleCollection->addEnabledAtFilter();
            $this->shippingMethodRuleCollection->addSortOrderOrder();
            $this->shippingMethodRuleCollection->load();
        }

        return $this->shippingMethodRuleCollection;
    }

    /**
     * @param Quote $quote
     * @param MarketplaceOrderInterface $marketplaceOrder
     * @param MarketplaceAddressInterface $marketplaceShippingAddress
     * @param StoreInterface $store
     * @throws LocalizedException
     */
    public function importQuoteShippingMethod(
        Quote $quote,
        MarketplaceOrderInterface $marketplaceOrder,
        MarketplaceAddressInterface $marketplaceShippingAddress,
        StoreInterface $store
    ) {
        $shippingMethodRuleCollection = $this->getShippingMethodRuleCollection();
        $quoteShippingAddress = $quote->getShippingAddress();
        $shippingRates = $quoteShippingAddress->getAllShippingRates();

        if ($quoteShippingAddress->hasData('cached_items_all')) {
            $quoteShippingAddress->unsetData('cached_items_all');
        }

        $quote->setTotalsCollectedFlag(false);
        $quote->collectTotals();

        if (empty($quoteShippingAddress->getData('total_qty'))) {
            // The "total_qty" value seems to sometimes be reset at the end of the collect process,
            // but it might be needed by some shipping method rules.
            $totalQty = 0;

            /** @var Quote\Item $quoteItem */
            foreach ($quote->getAllItems() as $quoteItem) {
                if (!$quoteItem->getParentItem()) {
                    $totalQty += $quoteItem->getQty();
                }
            }

            $quoteShippingAddress->setData('total_qty', $totalQty);
        }

        if (empty($shippingRates)) {
            $quoteShippingAddress->setCollectShippingRates(true);
            $quoteShippingAddress->collectShippingRates();
        }

        $shippingMethodApplier = null;
        $shippingMethodApplierResult = null;
        $shippingMethodApplierConfiguration = null;

        /** @var ShippingMethodRuleInterface $shippingMethodRule */
        foreach ($shippingMethodRuleCollection as $shippingMethodRule) {
            if ($shippingMethodRule->isAppliableToQuote($quote, $marketplaceOrder)) {
                try {
                    $shippingMethodApplier = $this->shippingMethodApplierPool->getApplierByCode(
                        $shippingMethodRule->getApplierCode()
                    );

                    $shippingMethodApplierConfiguration = $shippingMethodRule->getApplierConfiguration();

                    $shippingMethodApplierResult = $shippingMethodApplier->applyToQuoteShippingAddress(
                        $marketplaceOrder,
                        $marketplaceShippingAddress,
                        $quoteShippingAddress,
                        $shippingMethodApplierConfiguration
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
            $shippingMethodApplier = $this->shippingMethodApplierPool->getDefaultApplier();
            $shippingMethodApplierConfiguration = $this->dataObjectFactory->create();

            $shippingMethodApplierResult = $shippingMethodApplier->applyToQuoteShippingAddress(
                $marketplaceOrder,
                $marketplaceShippingAddress,
                $quoteShippingAddress,
                $shippingMethodApplierConfiguration
            );
        }

        if (null === $shippingMethodApplierResult) {
            throw new LocalizedException(__('No shipping method could be selected.'));
        }

        $quoteShippingAddress->removeAllShippingRates();
        $rateMethod = $this->shippingRateMethodFactory->create();

        $rateMethod->addData(
            [
                'carrier' => $shippingMethodApplierResult->getCarrierCode(),
                'carrier_title' => $shippingMethodApplierResult->getCarrierTitle(),
                'method' => $shippingMethodApplierResult->getMethodCode(),
                'method_title' => $shippingMethodApplierResult->getMethodTitle(),
                'cost' => $shippingMethodApplierResult->getCost(),
                // Shipping rates are expected to be in the base currency.
                'price' => $this->applyStoreCurrencyRateToAmount($shippingMethodApplierResult->getPrice(), $store),
            ]
        );

        $addressRate = $this->shippingAddressRateFactory->create();
        $addressRate->importShippingRate($rateMethod);
        $quoteShippingAddress->addShippingRate($addressRate);
        $quoteShippingAddress->setShippingMethod($shippingMethodApplierResult->getFullCode());

        $shippingMethodApplier->commitOnQuoteShippingAddress(
            $quoteShippingAddress,
            $shippingMethodApplierResult,
            $shippingMethodApplierConfiguration
        );
    }

    /**
     * @param Quote $quote
     * @param MarketplaceOrderInterface $marketplaceOrder
     * @param StoreInterface $store
     * @throws LocalizedException
     */
    public function importQuotePaymentMethod(
        Quote $quote,
        MarketplaceOrderInterface $marketplaceOrder,
        StoreInterface $store
    ) {
        $paymentMethodTitle = $this->orderGeneralConfig->getMarketplacePaymentMethodTitle(
            $store,
            $marketplaceOrder->getMarketplaceName()
        );

        $this->templateFilter->setVariables(
            [
                'marketplace' => $marketplaceOrder->getMarketplaceName(),
                'order_id' => $marketplaceOrder->getId(),
                'order_number' => $marketplaceOrder->getMarketplaceOrderNumber(),
                'payment_method' => $marketplaceOrder->getPaymentMethod(),
            ]
        );

        $paymentMethodTitle = trim((string) $paymentMethodTitle);

        if ('' !== $paymentMethodTitle) {
            try {
                $this->marketplacePaymentConfig->setForcedValue(
                    MarketplacePaymentConfig::FIELD_NAME_TITLE,
                    $this->templateFilter->filter($paymentMethodTitle)
                );
            } catch (\Exception $e) {
                $this->marketplacePaymentConfig->unsetForcedValue(MarketplacePaymentConfig::FIELD_NAME_TITLE);
            }
        } else {
            $this->marketplacePaymentConfig->unsetForcedValue(MarketplacePaymentConfig::FIELD_NAME_TITLE);
        }

        $quote->getPayment()->importData([ PaymentInterface::KEY_METHOD => PaymentConfigProvider::CODE ]);
    }

    public function isCurrentlyImportedQuote(Quote $quote)
    {
        return $this->currentlyImportedQuoteId === (int) $quote->getId();
    }

    /**
     * @param QuoteAddressInterface $quoteAddress
     * @return AddressExtensionInterface
     */
    private function getQuoteAddressExtensionAttributes(QuoteAddressInterface $quoteAddress)
    {
        if (!$extensionAttributes = $quoteAddress->getExtensionAttributes()) {
            $extensionAttributes = $this->quoteAddressExtensionFactory->create();
            $quoteAddress->setExtensionAttributes($extensionAttributes);
        }

        return $extensionAttributes;
    }

    /**
     * @param Quote $quote
     */
    private function applyBundleAdjustmentsOnQuote(Quote $quote)
    {
        if (!empty($this->currentlyImportedQuoteBundleAdjustments)) {
            $quoteAddress = $quote->getShippingAddress();
            $extensionAttributes = $this->getQuoteAddressExtensionAttributes($quoteAddress);
            $extensionAttributes->setSfmBundleAdjustments($this->currentlyImportedQuoteBundleAdjustments);
        }
    }

    /**
     * @param QuoteAddressInterface $quoteAddress
     * @param bool $isUntaxedBusinessOrder
     */
    private function tagImportedQuoteAddress(QuoteAddressInterface $quoteAddress, $isUntaxedBusinessOrder)
    {
        $extensionAttributes = $this->getQuoteAddressExtensionAttributes($quoteAddress);
        $extensionAttributes->setSfmIsShoppingFeedOrder(true);
        $extensionAttributes->setSfmIsShoppingFeedBusinessOrder($isUntaxedBusinessOrder);
    }

    public function tagImportedQuote(Quote $quote)
    {
        $quote->setData(self::QUOTE_KEY_IS_SHOPPING_FEED_ORDER, true);

        if ($this->isCurrentlyImportedBusinessQuote) {
            $quote->setData(self::QUOTE_KEY_IS_SHOPPING_FEED_BUSINESS_ORDER, true);
        }

        $this->tagImportedQuoteAddress(
            $quote->getBillingAddress(),
            $this->isCurrentlyImportedBusinessQuote
        );

        $this->tagImportedQuoteAddress(
            $quote->getShippingAddress(),
            $this->isCurrentlyImportedBusinessQuote
        );

        $this->applyBundleAdjustmentsOnQuote($quote);
    }

    public function isCurrentlyImportedSalesOrder(SalesOrderInterface $order)
    {
        return $this->currentlyImportedQuoteId === (int) $order->getQuoteId();
    }

    /**
     * @param SalesOrder $order
     * @throws LocalizedException
     * @throws \Exception
     */
    private function invoiceSalesOrder(SalesOrder $order)
    {
        if ($order->canInvoice()) {
            $invoice = $order->prepareInvoice();
            $invoice->register();
            $transaction = $this->transactionFactory->create();
            $transaction->addObject($invoice);
            $transaction->addObject($order);
            $transaction->save();

            // Compatibility with Mageplaza_CustomOrderNumber and Mageplaza_SameOrderNumber.
            $this->coreRegistry->unregister('con_new_invoice');
            $this->coreRegistry->unregister('son_new_invoice');
        }
    }

    /**
     * @param SalesOrder $order
     * @throws LocalizedException
     * @throws \Exception
     */
    private function shipSalesOrder(SalesOrder $order)
    {
        if ($order->canShip()) {
            $shippableItems = [];

            foreach ($order->getAllItems() as $orderItem) {
                $shippableQty = $orderItem->getQtyToShip();

                if (($shippableQty > 0) && !$orderItem->getIsVirtual()) {
                    $shippableItems[$orderItem->getId()] = $shippableQty;
                }
            }

            if (!empty($shippableItems)) {
                $shipment = $this->salesShipmentFactory->create($order, $shippableItems);
                $shipment->register();

                $transaction = $this->transactionFactory->create();
                $transaction->addObject($shipment);
                $transaction->addObject($order);
                $transaction->save();
            }
        }
    }

    /**
     * @param SalesOrderInterface $order
     * @throws LocalizedException
     * @throws \Exception
     */
    public function handleImportedSalesOrder(SalesOrderInterface $order)
    {
        if ($this->isImportRunning() && ($order instanceof SalesOrder)) {
            if ($this->orderGeneralConfig->shouldCreateInvoice($this->currentImportStore)) {
                $this->logDebugMessage('Creating invoice.');
                $this->invoiceSalesOrder($order);
            } else {
                $this->logDebugMessage('An invoice is not required.');
            }

            if (null !== $this->currentlyImportedMarketplaceOrder) {
                $shoppingFeedStatus = $this->currentlyImportedMarketplaceOrder->getShoppingFeedStatus();

                $shouldShipOrder = (
                        $this->currentlyImportedMarketplaceOrder->isFulfilled()
                        && $this->orderGeneralConfig->shouldCreateFulfilmentShipment($this->currentImportStore)
                    ) || (
                        !$this->currentlyImportedMarketplaceOrder->isFulfilled()
                        && (MarketplaceOrderInterface::STATUS_SHIPPED === $shoppingFeedStatus)
                        && $this->orderGeneralConfig->shouldCreateShippedShipment($this->currentImportStore)
                    );

                if ($shouldShipOrder) {
                    $this->logDebugMessage('Creating shipment.');
                    $this->shipSalesOrder($order);
                    $this->currentlyImportedMarketplaceOrder->setHasNonNotifiableShipment(true);
                } else {
                    $this->logDebugMessage('A shipment is not required.');
                }
            }
        }
    }

    public function isImportRunning()
    {
        return null !== $this->currentImportStore;
    }

    public function getImportRunningForStore()
    {
        return $this->currentImportStore;
    }

    public function getCurrentlyImportedMarketplaceOrder()
    {
        return $this->currentlyImportedMarketplaceOrder;
    }
}
