<?php

namespace ShoppingFeed\Manager\Model\Sales\Order;

use Magento\Catalog\Api\ProductRepositoryInterface as CatalogProductRepository;
use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Catalog\Helper\Product as CatalogProductHelper;
use Magento\Catalog\Model\Product\Attribute\Source\Status as CatalogProductStatus;
use Magento\Catalog\Model\Product\Type\AbstractType as ProductType;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filter\Template as TemplateFilter;
use Magento\Quote\Api\CartManagementInterface as QuoteManager;
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
use Magento\Tax\Model\Config as TaxConfig;
use Magento\Weee\Helper\Data as WeeeHelper;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\AddressInterface as MarketplaceAddressInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\ItemInterface as MarketplaceItemInterface;
use ShoppingFeed\Manager\Api\Data\Shipping\Method\RuleInterface as ShippingMethodRuleInterface;
use ShoppingFeed\Manager\Api\Marketplace\OrderRepositoryInterface as MarketplaceOrderRepositoryInterface;
use ShoppingFeed\Manager\DB\TransactionFactory;
use ShoppingFeed\Manager\Model\Marketplace\Order\Manager as MarketplaceOrderManager;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\OrderFactory as MarketplaceOrderResourceFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Address\CollectionFactory as MarketplaceAddressCollectionFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Item\CollectionFactory as MarketplaceItemCollectionFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Shipping\Method\Rule\Collection as ShippingMethodRuleCollection;
use ShoppingFeed\Manager\Model\ResourceModel\Shipping\Method\Rule\CollectionFactory as ShippingMethodRuleCollectionFactory;
use ShoppingFeed\Manager\Model\Sales\Order\Business\TaxManager as BusinessTaxManager;
use ShoppingFeed\Manager\Model\Sales\Order\ConfigInterface as OrderConfigInterface;
use ShoppingFeed\Manager\Model\Sales\Order\Customer\Importer as CustomerImporter;
use ShoppingFeed\Manager\Model\Shipping\Method\ApplierPoolInterface as ShippingMethodApplierPoolInterface;
use ShoppingFeed\Manager\Model\TimeHelper;
use ShoppingFeed\Manager\Model\Ui\Payment\ConfigProvider as PaymentConfigProvider;
use ShoppingFeed\Manager\Payment\Gateway\Config\Config as MarketplacePaymentConfig;
use ShoppingFeed\Manager\Plugin\Tax\ConfigPlugin as TaxConfigPlugin;
use ShoppingFeed\Manager\Plugin\Weee\TaxPlugin as WeeeTaxPlugin;

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
     * @var TemplateFilter
     */
    private $templateFilter;

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
     * @var bool
     */
    private $isCurrentlyImportedBusinessQuote = false;

    /**
     * @param TransactionFactory $transactionFactory
     * @param DataObjectFactory $dataObjectFactory
     * @param TimeHelper $timeHelper
     * @param TemplateFilter $templateFilter
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
     * @param MarketplacePaymentConfig $marketplacePaymentConfig
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
        TemplateFilter $templateFilter,
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
        MarketplacePaymentConfig $marketplacePaymentConfig,
        MarketplaceOrderManager $marketplaceOrderManager,
        MarketplaceOrderRepositoryInterface $marketplaceOrderRepository,
        MarketplaceOrderResourceFactory $marketplaceOrderResourceFactory,
        MarketplaceAddressCollectionFactory $marketplaceAddressCollectionFactory,
        MarketplaceItemCollectionFactory $marketplaceItemCollectionFactory
    ) {
        $this->transactionFactory = $transactionFactory;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->timeHelper = $timeHelper;
        $this->templateFilter = $templateFilter;
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
        $this->marketplacePaymentConfig = $marketplacePaymentConfig;
        $this->marketplaceOrderManager = $marketplaceOrderManager;
        $this->marketplaceOrderRepository = $marketplaceOrderRepository;
        $this->marketplaceOrderResourceFactory = $marketplaceOrderResourceFactory;
        $this->marketplaceAddressCollectionFactory = $marketplaceAddressCollectionFactory;
        $this->marketplaceItemCollectionFactory = $marketplaceItemCollectionFactory;
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

        try {
            foreach ($marketplaceOrders as $marketplaceOrder) {
                $marketplaceOrderId = $marketplaceOrder->getId();

                try {
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

                    $quoteId = (int) $this->quoteManager->createEmptyCart();
                    $this->currentlyImportedQuoteId = $quoteId;

                    /** @var Quote $quote */
                    $quote = $this->quoteRepository->get($quoteId);

                    if (!$this->orderGeneralConfig->shouldCheckProductAvailabilityAndOptions($store)) {
                        $quote->setIsSuperMode(true);
                        $this->catalogProductHelper->setSkipSaleableCheck(true);
                    } else {
                        $quote->setIsSuperMode(false);
                        $this->catalogProductHelper->setSkipSaleableCheck(false);
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

                    $this->customerImporter->importQuoteCustomer(
                        $quote,
                        $marketplaceOrder,
                        $orderAddresses[$marketplaceOrderId][MarketplaceAddressInterface::TYPE_BILLING],
                        $store
                    );

                    $this->importQuoteAddress(
                        $quote,
                        $marketplaceOrder,
                        $orderAddresses[$marketplaceOrderId][MarketplaceAddressInterface::TYPE_BILLING],
                        $isUntaxedBusinessOrder,
                        $store
                    );

                    $this->importQuoteAddress(
                        $quote,
                        $marketplaceOrder,
                        $orderAddresses[$marketplaceOrderId][MarketplaceAddressInterface::TYPE_SHIPPING],
                        $isUntaxedBusinessOrder,
                        $store
                    );

                    if ($isUntaxedBusinessOrder) {
                        $this->isCurrentlyImportedBusinessQuote = true;
                        $quote->setData(self::QUOTE_KEY_IS_SHOPPING_FEED_BUSINESS_ORDER, true);
                        $quote->setCustomerGroupId($this->businessTaxManager->getCustomerGroup()->getId());
                        $quote->setCustomerTaxClassId($this->businessTaxManager->getCustomerTaxClass()->getClassId());
                    } else {
                        $this->isCurrentlyImportedBusinessQuote = false;
                    }

                    $this->importQuoteItems(
                        $quote,
                        $orderItems[$marketplaceOrderId],
                        $isUntaxedBusinessOrder,
                        $store
                    );

                    $this->importQuoteShippingMethod(
                        $quote,
                        $marketplaceOrder,
                        $orderAddresses[$marketplaceOrderId][MarketplaceAddressInterface::TYPE_SHIPPING],
                        $store
                    );

                    $this->importQuotePaymentMethod($quote, $marketplaceOrder, $store);

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
            $this->taxConfigPlugin->disableForcedCrossBorderTrade();
            $this->currentImportStore = null;
            $this->currentlyImportedMarketplaceOrder = null;
            $this->currentlyImportedQuoteId = null;
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
        $shouldUseItemReferenceAsProductId = $this->orderGeneralConfig->shouldUseItemReferenceAsProductId($store);
        $isWeeeEnabled = $this->weeeHelper->isEnabled($store->getBaseStore());

        /** @var MarketplaceItemInterface $marketplaceItem */
        foreach ($marketplaceItems as $marketplaceItem) {
            $reference = $marketplaceItem->getReference();
            $quoteStoreId = $quote->getStoreId();

            try {
                /** @var CatalogProduct $product */
                $product = null;

                try {
                    if (!$shouldUseItemReferenceAsProductId || !ctype_digit(trim($reference))) {
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

                if ($this->orderGeneralConfig->shouldCheckProductAvailabilityAndOptions($store)
                    && ((int) $product->getStatus() === CatalogProductStatus::STATUS_DISABLED)
                ) {
                    throw new LocalizedException(__('The product with reference "%1" is disabled.', $reference));
                }

                if ($this->orderGeneralConfig->shouldCheckProductWebsites($store)
                    && !in_array($store->getBaseWebsiteId(), array_map('intval', $product->getWebsiteIds()), true)
                ) {
                    throw new LocalizedException(
                        __('The product with reference "%1" is not available in the website.', $reference)
                    );
                }

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

        $paymentMethodTitle = trim($paymentMethodTitle);

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
     * @param bool $isUntaxedBusinessOrder
     */
    private function tagImportedQuoteAddress(QuoteAddressInterface $quoteAddress, $isUntaxedBusinessOrder)
    {
        if (!$extensionAttributes = $quoteAddress->getExtensionAttributes()) {
            $extensionAttributes = $this->quoteAddressExtensionFactory->create();
        }

        $extensionAttributes->setSfmIsShoppingFeedOrder(true);
        $extensionAttributes->setSfmIsShoppingFeedBusinessOrder($isUntaxedBusinessOrder);
        $quoteAddress->setExtensionAttributes($extensionAttributes);
    }

    public function tagImportedQuote(Quote $quote)
    {
        $quote->setData(self::QUOTE_KEY_IS_SHOPPING_FEED_ORDER, true);

        if ($this->isCurrentlyImportedBusinessQuote) {
            $quote->setData(self::QUOTE_KEY_IS_SHOPPING_FEED_BUSINESS_ORDER, true);
        }

        if ($quoteAddress = $quote->getBillingAddress()) {
            $this->tagImportedQuoteAddress($quoteAddress, $this->isCurrentlyImportedBusinessQuote);
        }

        if ($quoteAddress = $quote->getShippingAddress()) {
            $this->tagImportedQuoteAddress($quoteAddress, $this->isCurrentlyImportedBusinessQuote);
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
        if ($this->isImportRunning()
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
