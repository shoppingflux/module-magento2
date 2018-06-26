<?php

namespace ShoppingFeed\Manager\Model\Sales\Order;

use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface as SalesOrderInterface;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;


interface ImporterInterface
{
    const QUOTE_KEY_IS_SHOPPING_FEED_ORDER = 'sfm_is_shopping_feed_order';

    /**
     * @param StoreInterface $store
     */
    public function importOrders(StoreInterface $store);

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
     * @return mixed
     */
    public function isCurrentlyImportedSalesOrder(SalesOrderInterface $order);

    // @todo method to associate marketplace order to sales order
}
