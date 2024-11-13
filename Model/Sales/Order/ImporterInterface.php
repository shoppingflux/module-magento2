<?php

namespace ShoppingFeed\Manager\Model\Sales\Order;

use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface as SalesOrderInterface;
use Magento\Store\Model\Store as BaseStore;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\AddressInterface as MarketplaceAddressInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\ItemInterface as MarketplaceItemInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;

interface ImporterInterface
{
    const QUOTE_KEY_IS_SHOPPING_FEED_ORDER = 'sfm_is_shopping_feed_order';
    const QUOTE_KEY_IS_SHOPPING_FEED_BUSINESS_ORDER = 'sfm_is_shopping_feed_business_order';
    const QUOTE_KEY_STORE = 'sfm_store';

    /**
     * @param StoreInterface $store
     * @param MarketplaceOrderInterface $order
     * @param MarketplaceAddressInterface $billingAddress
     * @param MarketplaceAddressInterface $shippingAddress
     * @return BaseStore
     */
    public function getOrderTargetBaseStore(
        StoreInterface $store,
        MarketplaceOrderInterface $order,
        MarketplaceAddressInterface $billingAddress,
        MarketplaceAddressInterface $shippingAddress
    );

    /**
     * @param StoreInterface $store
     * @return BaseStore
     */
    public function getCurrentTargetBaseStore(StoreInterface $store);

    /**
     * @param MarketplaceOrderInterface[] $marketplaceOrders
     * @param StoreInterface $store
     */
    public function importStoreOrders(array $marketplaceOrders, StoreInterface $store);

    /**
     * @param Quote $quote
     * @param MarketplaceOrderInterface $marketplaceOrder
     * @param MarketplaceAddressInterface $marketplaceAddress
     * @param bool $isUntaxedBusinessOrder
     * @param StoreInterface $store
     */
    public function importQuoteAddress(
        Quote $quote,
        MarketplaceOrderInterface $marketplaceOrder,
        MarketplaceAddressInterface $marketplaceAddress,
        $isUntaxedBusinessOrder,
        StoreInterface $store
    );

    /**
     * @param Quote $quote
     * @param MarketplaceItemInterface[] $marketplaceItems
     * @param bool $isUntaxedBusinessOrder
     * @param StoreInterface $store
     */
    public function importQuoteItems(
        Quote $quote,
        array $marketplaceItems,
        $isUntaxedBusinessOrder,
        StoreInterface $store
    );

    /**
     * @param Quote $quote
     * @param MarketplaceOrderInterface $marketplaceOrder
     * @param MarketplaceAddressInterface $marketplaceShippingAddress
     * @param StoreInterface $store
     */
    public function importQuoteShippingMethod(
        Quote $quote,
        MarketplaceOrderInterface $marketplaceOrder,
        MarketplaceAddressInterface $marketplaceShippingAddress,
        StoreInterface $store
    );

    /**
     * @param Quote $quote
     * @param MarketplaceOrderInterface $marketplaceOrder
     * @param StoreInterface $store
     */
    public function importQuotePaymentMethod(
        Quote $quote,
        MarketplaceOrderInterface $marketplaceOrder,
        StoreInterface $store
    );

    /**
     * @param Quote $quote
     * @return bool
     */
    public function isCurrentlyImportedQuote(Quote $quote);

    /**
     * @param Quote $quote
     */
    public function tagImportedQuote(Quote $quote);

    /**
     * @param SalesOrderInterface $order
     * @return bool
     */
    public function isCurrentlyImportedSalesOrder(SalesOrderInterface $order);

    /**
     * @param SalesOrderInterface $order
     */
    public function handleImportedSalesOrder(SalesOrderInterface $order);

    /**
     * @return bool
     */
    public function isImportRunning();

    /**
     * @return StoreInterface|null
     */
    public function getImportRunningForStore();

    /**
     * @return MarketplaceOrderInterface|null
     */
    public function getCurrentlyImportedMarketplaceOrder();
}
