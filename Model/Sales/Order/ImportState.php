<?php

namespace ShoppingFeed\Manager\Model\Sales\Order;

use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface as SalesOrderInterface;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;

class ImportState implements ImportStateInterface
{
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
     * @return bool
     */
    public function isImportRunning()
    {
        return null !== $this->currentImportStore;
    }

    /**
     * @return StoreInterface|null
     */
    public function getImportRunningForStore()
    {
        return $this->currentImportStore;
    }

    /**
     * @return MarketplaceOrderInterface|null
     */
    public function getCurrentlyImportedMarketplaceOrder()
    {
        return $this->currentlyImportedMarketplaceOrder;
    }

    /**
     * @param Quote $quote
     * @return bool
     */
    public function isCurrentlyImportedQuote(Quote $quote)
    {
        return $this->currentlyImportedQuoteId === (int) $quote->getId();
    }

    /**
     * @param SalesOrderInterface $order
     * @return bool
     */
    public function isCurrentlyImportedSalesOrder(SalesOrderInterface $order)
    {
        return $this->currentlyImportedQuoteId === (int) $order->getQuoteId();
    }

    /**
     * @return bool
     */
    public function isCurrentlyImportedBusinessQuote()
    {
        return $this->isCurrentlyImportedBusinessQuote;
    }

    /**
     * @param StoreInterface|null $store
     */
    public function setCurrentImportStore(?StoreInterface $store)
    {
        $this->currentImportStore = $store;
    }

    /**
     * @param MarketplaceOrderInterface|null $order
     */
    public function setCurrentlyImportedMarketplaceOrder(?MarketplaceOrderInterface $order)
    {
        $this->currentlyImportedMarketplaceOrder = $order;
    }

    /**
     * @param int|null $quoteId
     */
    public function setCurrentlyImportedQuoteId($quoteId)
    {
        $this->currentlyImportedQuoteId = $quoteId;
    }

    /**
     * @param bool $isBusinessQuote
     */
    public function setIsCurrentlyImportedBusinessQuote($isBusinessQuote)
    {
        $this->isCurrentlyImportedBusinessQuote = $isBusinessQuote;
    }

    public function reset()
    {
        $this->currentImportStore = null;
        $this->currentlyImportedMarketplaceOrder = null;
        $this->currentlyImportedQuoteId = null;
        $this->isCurrentlyImportedBusinessQuote = false;
    }
}
