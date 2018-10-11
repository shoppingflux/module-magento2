<?php

namespace ShoppingFeed\Manager\Model\Sales\Order;

use Magento\Quote\Api\Data\AddressInterface as QuoteAddressInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface as SalesOrderInterface;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\AddressInterface as MarketplaceAddressInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\ItemInterface as MarketplaceItemInterface;

interface ImporterInterface
{
    const QUOTE_KEY_IS_SHOPPING_FEED_ORDER = 'sfm_is_shopping_feed_order';
    const QUOTE_KEY_STORE = 'sfm_store';

    /**
     * @param array MarketplaceOrderInterface[] $marketplaceOrders
     * @param StoreInterface $store
     */
    public function importStoreOrders(array $marketplaceOrders, StoreInterface $store);

    /**
     * @param QuoteAddressInterface $quoteAddress
     * @param MarketplaceAddressInterface $marketplaceAddress
     * @param StoreInterface $store
     */
    public function importQuoteAddress(
        QuoteAddressInterface $quoteAddress,
        MarketplaceAddressInterface $marketplaceAddress,
        StoreInterface $store
    );

    /**
     * @param Quote $quote
     * @param MarketplaceItemInterface[] $marketplaceItems
     * @param StoreInterface $store
     */
    public function importQuoteItems(Quote $quote, array $marketplaceItems, StoreInterface $store);

    /**
     * @param Quote $quote
     * @param MarketplaceOrderInterface $marketplaceOrder
     * @param StoreInterface $store
     */
    public function importQuoteShippingMethod(
        Quote $quote,
        MarketplaceOrderInterface $marketplaceOrder,
        StoreInterface $store
    );

    /**
     * @param Quote $quote
     * @param StoreInterface $store
     */
    public function importQuotePaymentMethod(Quote $quote, StoreInterface $store);

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
}
