<?php

namespace ShoppingFeed\Manager\Model\Sales\Order;

use Magento\Sales\Model\Order as SalesOrder;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;

interface SyncerInterface
{
    const SYNCING_ACTION_NONE = 'none';
    const SYNCING_ACTION_HOLD = 'hold';
    const SYNCING_ACTION_CANCEL = 'cancel';
    const SYNCING_ACTION_REFUND = 'refund';
    const SYNCING_ACTION_CANCEL_OR_REFUND = 'cancel_or_refund';

    /**
     * @param MarketplaceOrderInterface[] $marketplaceOrders
     * @param StoreInterface $store
     */
    public function synchronizeStoreOrders(array $marketplaceOrders, StoreInterface $store);

    /**
     * @param MarketplaceOrderInterface $marketplaceOrder
     * @param SalesOrder $salesOrder
     * @param StoreInterface $store
     */
    public function holdStoreOrder(
        MarketplaceOrderInterface $marketplaceOrder,
        SalesOrder $salesOrder,
        StoreInterface $store
    );

    /**
     * @param MarketplaceOrderInterface $marketplaceOrder
     * @param SalesOrder $salesOrder
     * @param StoreInterface $store
     */
    public function cancelStoreOrder(
        MarketplaceOrderInterface $marketplaceOrder,
        SalesOrder $salesOrder,
        StoreInterface $store
    );

    /**
     * @param MarketplaceOrderInterface $marketplaceOrder
     * @param SalesOrder $salesOrder
     * @param StoreInterface $store
     */
    public function refundStoreOrder(
        MarketplaceOrderInterface $marketplaceOrder,
        SalesOrder $salesOrder,
        StoreInterface $store
    );
}
