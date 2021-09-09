<?php

namespace ShoppingFeed\Manager\Plugin\CatalogInventory\Observer;

use Magento\CatalogInventory\Observer\CancelOrderItemObserver;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Exception\NoSuchEntityException;use Magento\Sales\Model\Order\Item as SalesOrderItem;
use ShoppingFeed\Manager\Api\Marketplace\OrderRepositoryInterface as MarketplaceOrderRepositoryInterface;

class CancelOrderItemObserverPlugin
{
    const EVENT_KEY_ITEM = 'item';

    /**
     * @var MarketplaceOrderRepositoryInterface
     */
    private $marketplaceOrderRepository;

    /**
     * @param MarketplaceOrderRepositoryInterface $marketplaceOrderRepository
     */
    public function __construct(MarketplaceOrderRepositoryInterface $marketplaceOrderRepository)
    {
        $this->marketplaceOrderRepository = $marketplaceOrderRepository;
    }

    /**
     * @param CancelOrderItemObserver $subject
     * @param callable $proceed
     * @param EventObserver $observer
     */
    public function aroundExecute(CancelOrderItemObserver $subject, callable $proceed, EventObserver $observer)
    {
        $isMarketplaceFulfilledOrder = false;
        $salesOrderItem = $observer->getEvent()->getData(static::EVENT_KEY_ITEM);

        try {
            if (
                ($salesOrderItem instanceof SalesOrderItem)
                && ($salesOrderId = (int) $salesOrderItem->getOrderId())
                && ($marketplaceOrder = $this->marketplaceOrderRepository->getBySalesOrderId($salesOrderId))
                && $marketplaceOrder->isFulfilled()
            ) {
                $isMarketplaceFulfilledOrder = true;
            }
        } catch (\Exception $e) {
            // Not a marketplace order.
        }

        if (!$isMarketplaceFulfilledOrder) {
            $proceed($observer);
        }
    }
}
