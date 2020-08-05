<?php

namespace ShoppingFeed\Manager\Model\Sales\Order;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface as SalesOrderRepositoryInterface;
use Magento\Sales\Model\Order as SalesOrder;
use Magento\Sales\Model\Order\CreditmemoFactory;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;
use ShoppingFeed\Manager\Model\Marketplace\Order\Manager as MarketplaceOrderManager;
use ShoppingFeed\Manager\Model\Sales\Order\ConfigInterface as OrderConfigInterface;

class Syncer implements SyncerInterface
{
    /**
     * @var OrderConfigInterface
     */
    private $orderGeneralConfig;

    /**
     * @var MarketplaceOrderManager
     */
    private $marketplaceOrderManager;

    /**
     * @var SalesOrderRepositoryInterface
     */
    private $salesOrderRepository;

    /**
     * @var CreditmemoFactory
     */
    private $creditmemoFactory;

    /**
     * @var CreditmemoManagementInterface
     */
    private $creditmemoManager;

    /**
     * @param ConfigInterface $orderGeneralConfig
     * @param MarketplaceOrderManager $marketplaceOrderManager
     * @param SalesOrderRepositoryInterface $salesOrderRepository
     * @param CreditmemoFactory $creditmemoFactory
     * @param CreditmemoManagementInterface $creditmemoManager
     */
    public function __construct(
        OrderConfigInterface $orderGeneralConfig,
        MarketplaceOrderManager $marketplaceOrderManager,
        SalesOrderRepositoryInterface $salesOrderRepository,
        CreditmemoFactory $creditmemoFactory,
        CreditmemoManagementInterface $creditmemoManager
    ) {
        $this->orderGeneralConfig = $orderGeneralConfig;
        $this->marketplaceOrderManager = $marketplaceOrderManager;
        $this->salesOrderRepository = $salesOrderRepository;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->creditmemoManager = $creditmemoManager;
    }

    public function synchronizeStoreOrders(array $marketplaceOrders, StoreInterface $store)
    {
        /** @var MarketplaceOrderInterface $marketplaceOrder */
        foreach ($marketplaceOrders as $marketplaceOrder) {
            if (!$salesOrderId = $marketplaceOrder->getSalesOrderId()) {
                continue;
            }

            try {
                $salesOrder = $this->salesOrderRepository->get($marketplaceOrder->getSalesOrderId());
            } catch (NoSuchEntityException $e) {
                continue;
            }

            if (!$salesOrder instanceof SalesOrder) {
                continue;
            }

            $action = null;

            switch ($marketplaceOrder->getShoppingFeedStatus()) {
                case MarketplaceOrderInterface::STATUS_REFUSED:
                    $action = $this->orderGeneralConfig->getOrderRefusalSyncingAction($store);
                    break;
                case MarketplaceOrderInterface::STATUS_CANCELLED:
                    $action = $this->orderGeneralConfig->getOrderCancellationSyncingAction($store);
                    break;
                case MarketplaceOrderInterface::STATUS_REFUNDED:
                    $action = $this->orderGeneralConfig->getOrderRefundSyncingAction($store);
                    break;
            }

            try {
                if (self::SYNCING_ACTION_CANCEL_OR_REFUND === $action) {
                    if ($salesOrder->canCancel()) {
                        $action = self::SYNCING_ACTION_CANCEL;
                    } elseif ($salesOrder->canCreditmemo()) {
                        $action = self::SYNCING_ACTION_REFUND;
                    }
                }

                if (self::SYNCING_ACTION_HOLD === $action) {
                    $this->holdStoreOrder($marketplaceOrder, $salesOrder, $store);
                } elseif (self::SYNCING_ACTION_CANCEL === $action) {
                    $this->cancelStoreOrder($marketplaceOrder, $salesOrder, $store);
                } elseif (self::SYNCING_ACTION_REFUND === $action) {
                    $this->refundStoreOrder($marketplaceOrder, $salesOrder, $store);
                }
            } catch (\Exception $e) {
                $this->marketplaceOrderManager->logOrderError(
                    $marketplaceOrder,
                    __('Could not synchronize the imported order:') . "\n" . $e->getMessage(),
                    (string) $e
                );
            }
        }
    }

    public function holdStoreOrder(
        MarketplaceOrderInterface $marketplaceOrder,
        SalesOrder $salesOrder,
        StoreInterface $store
    ) {
        if ($salesOrder->canHold()) {
            $salesOrder->hold();
            $this->salesOrderRepository->save($salesOrder);
        }
    }

    public function cancelStoreOrder(
        MarketplaceOrderInterface $marketplaceOrder,
        SalesOrder $salesOrder,
        StoreInterface $store
    ) {
        if ($salesOrder->canCancel()) {
            $salesOrder->cancel();
            $this->salesOrderRepository->save($salesOrder);
        }
    }

    public function refundStoreOrder(
        MarketplaceOrderInterface $marketplaceOrder,
        SalesOrder $salesOrder,
        StoreInterface $store
    ) {
        if ($salesOrder->canCreditmemo()) {
            $creditmemo = $this->creditmemoFactory->createByOrder($salesOrder);

            foreach ($creditmemo->getAllItems() as $creditmemoItem) {
                $creditmemoItem->setBackToStock(true);
            }

            $this->creditmemoManager->refund($creditmemo, true);
        }
    }
}
