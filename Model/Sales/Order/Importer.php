<?php

namespace ShoppingFeed\Manager\Model\Sales\Order;

use Magento\Catalog\Api\ProductRepositoryInterface as CatalogProductRepositoryInterface;
use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Catalog\Model\Product\Type\AbstractType as ProductType;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartManagementInterface as QuoteManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface as QuoteRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface as QuoteAddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\Data\AddressExtensionFactory as QuoteAddressExtensionFactory;
use Magento\Quote\Model\Quote\Address\RateFactory as ShippingAddressRateFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory as ShippingRateMethodFactory;
use Magento\Sales\Api\Data\OrderInterface as SalesOrderInterface;
use Magento\Store\Model\Store as BaseStore;
use Magento\Store\Model\StoreManagerInterface as BaseStoreManagerInterface;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\AddressInterface as MarketplaceAddressInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\ItemInterface as MarketplaceItemInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\CollectionFactory as MarketplaceOrderCollectionFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Address\CollectionFactory as MarketplaceAddressCollectionFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Item\CollectionFactory as MarketplaceItemCollectionFactory;
use ShoppingFeed\Manager\Model\Ui\Payment\ConfigProvider as PaymentConfigProvider;


class Importer implements ImporterInterface
{
    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var BaseStoreManagerInterface
     */
    private $baseStoreManager;

    /**
     * @var CatalogProductRepositoryInterface
     */
    private $catalogProductRepository;

    /**
     * @var QuoteManagerInterface
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
     * @var MarketplaceOrderCollectionFactory
     */
    private $marketplaceOrderCollectionFactory;

    /**
     * @var MarketplaceAddressCollectionFactory
     */
    private $marketplaceAddressCollectionFactory;

    /**
     * @var MarketplaceItemCollectionFactory
     */
    private $marketplaceItemCollectionFactory;

    /**
     * @var int|null
     */
    private $currentlyImportedQuoteId = null;

    /**
     * @param DataObjectFactory $dataObjectFactory
     * @param BaseStoreManagerInterface $baseStoreManager
     * @param CatalogProductRepositoryInterface $catalogProductRepository
     * @param QuoteManagerInterface $quoteManager
     * @param QuoteRepositoryInterface $quoteRepository
     * @param QuoteAddressExtensionFactory $quoteAddressExtensionFactory
     * @param ShippingRateMethodFactory $shippingRateMethodFactory
     * @param ShippingAddressRateFactory $shippingAddressRateFactory
     * @param MarketplaceOrderCollectionFactory $marketplaceOrderCollectionFactory
     * @param MarketplaceAddressCollectionFactory $marketplaceAddressCollectionFactory
     * @param MarketplaceItemCollectionFactory $marketplaceItemCollectionFactory
     */
    public function __construct(
        DataObjectFactory $dataObjectFactory,
        BaseStoreManagerInterface $baseStoreManager,
        CatalogProductRepositoryInterface $catalogProductRepository,
        QuoteManagerInterface $quoteManager,
        QuoteRepositoryInterface $quoteRepository,
        QuoteAddressExtensionFactory $quoteAddressExtensionFactory,
        ShippingRateMethodFactory $shippingRateMethodFactory,
        ShippingAddressRateFactory $shippingAddressRateFactory,
        MarketplaceOrderCollectionFactory $marketplaceOrderCollectionFactory,
        MarketplaceAddressCollectionFactory $marketplaceAddressCollectionFactory,
        MarketplaceItemCollectionFactory $marketplaceItemCollectionFactory
    ) {
        $this->dataObjectFactory = $dataObjectFactory;
        $this->baseStoreManager = $baseStoreManager;
        $this->catalogProductRepository = $catalogProductRepository;
        $this->quoteManager = $quoteManager;
        $this->quoteRepository = $quoteRepository;
        $this->quoteAddressExtensionFactory = $quoteAddressExtensionFactory;
        $this->shippingRateMethodFactory = $shippingRateMethodFactory;
        $this->shippingAddressRateFactory = $shippingAddressRateFactory;
        $this->marketplaceOrderCollectionFactory = $marketplaceOrderCollectionFactory;
        $this->marketplaceAddressCollectionFactory = $marketplaceAddressCollectionFactory;
        $this->marketplaceItemCollectionFactory = $marketplaceItemCollectionFactory;
    }

    /**
     * @param QuoteAddressInterface $quoteAddress
     * @param MarketplaceAddressInterface $marketplaceAddress
     */
    private function importQuoteAddress(
        QuoteAddressInterface $quoteAddress,
        MarketplaceAddressInterface $marketplaceAddress
    ) {
        // @todo import rules
        $quoteAddress->setFirstname($marketplaceAddress->getFirstName());
        $quoteAddress->setLastname($marketplaceAddress->getLastName());
        $quoteAddress->setStreet($marketplaceAddress->getStreet());
        $quoteAddress->setPostcode($marketplaceAddress->getPostalCode());
        $quoteAddress->setCity($marketplaceAddress->getCity());
        $quoteAddress->setCountryId($marketplaceAddress->getCountryCode());
        $quoteAddress->setTelephone($marketplaceAddress->getPhone());
        $quoteAddress->setEmail($marketplaceAddress->getEmail());
        $this->tagImportedQuoteAddress($quoteAddress);
    }

    /**
     * @param Quote $quote
     * @param array $marketplaceItems
     * @throws LocalizedException
     */
    private function importQuoteItems(Quote $quote, array $marketplaceItems)
    {
        /** @var MarketplaceItemInterface $marketplaceItem */
        foreach ($marketplaceItems as $marketplaceItem) {
            try {
                /** @var CatalogProduct $product */
                $product = $this->catalogProductRepository->get(
                    $marketplaceItem->getReference(),
                    false,
                    $quote->getStoreId(),
                    false
                );

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
                throw new LocalizedException(__('todo'));
            } catch (\Exception $e) {
                throw new LocalizedException(__('todo'));
            }
        }
    }

    /**
     * @param Quote $quote
     * @param MarketplaceOrderInterface $marketplaceOrder
     */
    private function importQuoteShippingMethod(Quote $quote, MarketplaceOrderInterface $marketplaceOrder)
    {
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(false);
        $shippingAddress->removeAllShippingRates();
        $rateMethod = $this->shippingRateMethodFactory->create();

        $rateMethod->addData(
            [
                'carrier' => 'freeshipping',
                'carrier_title' => 'Carrier',
                'method' => 'freeshipping',
                'method_title' => 'Method',
                'cost' => $marketplaceOrder->getShippingAmount(),
                'price' => $marketplaceOrder->getShippingAmount(),
            ]
        );

        $addressRate = $this->shippingAddressRateFactory->create();
        $addressRate->importShippingRate($rateMethod);
        $shippingAddress->addShippingRate($addressRate);
        $shippingAddress->setShippingMethod('freeshipping_freeshipping');
    }

    /**
     * @param Quote $quote
     * @throws LocalizedException
     */
    private function importQuotePaymentMethod(Quote $quote)
    {
        $quote->getPayment()->importData([ PaymentInterface::KEY_METHOD => PaymentConfigProvider::CODE ]);
    }

    /**
     * @param StoreInterface $store
     * @throws LocalizedException
     * @throws \Exception
     */
    public function importOrders(StoreInterface $store)
    {
        /** @var BaseStore $baseStore */
        $baseStore = $store->getBaseStore();

        $orderCollection = $this->marketplaceOrderCollectionFactory->create();
        $orderCollection->addNonImportedFilter();
        $orderCollection->addStoreIdFilter($store->getId());
        $orderCollection->load();
        $orderIds = $orderCollection->getLoadedIds();

        if (empty($orderIds)) {
            return;
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

        /** @var MarketplaceOrderInterface $marketplaceOrder */
        foreach ($orderCollection as $marketplaceOrder) {
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

                if (($quoteAddress = $quote->getBillingAddress())
                    && isset($orderAddresses[$marketplaceOrderId][MarketplaceAddressInterface::TYPE_BILLING])
                ) {
                    $this->importQuoteAddress(
                        $quoteAddress,
                        $orderAddresses[$marketplaceOrderId][MarketplaceAddressInterface::TYPE_BILLING]
                    );
                } else {
                    throw new LocalizedException(__('todo'));
                }

                if (($quoteAddress = $quote->getShippingAddress())
                    && isset($orderAddresses[$marketplaceOrderId][MarketplaceAddressInterface::TYPE_SHIPPING])
                ) {
                    $this->importQuoteAddress(
                        $quoteAddress,
                        $orderAddresses[$marketplaceOrderId][MarketplaceAddressInterface::TYPE_SHIPPING]
                    );
                } else {
                    throw new LocalizedException(__('todo'));
                }

                if (isset($orderItems[$marketplaceOrderId])) {
                    $this->importQuoteItems($quote, $orderItems[$marketplaceOrderId]);
                } else {
                    throw new LocalizedException(__('todo'));
                }

                $this->importQuoteShippingMethod($quote, $marketplaceOrder);
                $this->importQuotePaymentMethod($quote);
                $this->quoteRepository->save($quote);
                // transaction with:
                $orderId = $this->quoteManager->placeOrder($quoteId);
                // $marketplaceOrder->setSalesOrderId($orderId); // @todo + set imported at
                // $marketplaceOrderRepository->save($marketplaceOrder);
                // 

            } catch (LocalizedException $e) {
                throw $e;
            } catch (\Exception $e) {
                throw $e;
            }
        }

        $this->currentlyImportedQuoteId = null;
        $baseStore->setCurrentCurrencyCode($originalBaseStoreCurrencyCode);
        $this->baseStoreManager->setCurrentStore($originalCurrentBaseStore);
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
}
