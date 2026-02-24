<?php

namespace ShoppingFeed\Manager\Model\Sales\Order;

use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface as SalesOrderInterface;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;

interface ImportStateInterface
{
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

    /**
     * @param Quote $quote
     * @return bool
     */
    public function isCurrentlyImportedQuote(Quote $quote);

    /**
     * @param SalesOrderInterface $order
     * @return bool
     */
    public function isCurrentlyImportedSalesOrder(SalesOrderInterface $order);

    /**
     * @return bool
     */
    public function isCurrentlyImportedBusinessQuote();

    /**
     * @param StoreInterface|null $store
     */
    public function setCurrentImportStore(?StoreInterface $store);

    /**
     * @param MarketplaceOrderInterface|null $order
     */
    public function setCurrentlyImportedMarketplaceOrder(?MarketplaceOrderInterface $order);

    /**
     * @param int|null $quoteId
     */
    public function setCurrentlyImportedQuoteId($quoteId);

    /**
     * @param bool $isBusinessQuote
     */
    public function setIsCurrentlyImportedBusinessQuote($isBusinessQuote);

    public function reset();
}
