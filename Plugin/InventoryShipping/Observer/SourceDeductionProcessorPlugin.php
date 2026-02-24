<?php

namespace ShoppingFeed\Manager\Plugin\InventoryShipping\Observer;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\InventoryShipping\Observer\SourceDeductionProcessor;
use Magento\Sales\Model\Order\Shipment;
use ShoppingFeed\Manager\Api\Marketplace\OrderRepositoryInterface as MarketplaceOrderRepositoryInterface;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as SalesOrderImporterInterface;
use ShoppingFeed\Manager\Model\Sales\Order\ImportStateInterface as SalesOrderImportStateInterface;
use ShoppingFeed\Manager\Observer\BeforeQuoteSubmitObserver;

class SourceDeductionProcessorPlugin
{
    const EVENT_KEY_SHIPMENT = 'shipment';

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var MarketplaceOrderRepositoryInterface
     */
    private $marketplaceOrderRepository;

    /**
     * @var SalesOrderImporterInterface
     */
    private $salesOrderImporter;

    /**
     * @var SalesOrderImportStateInterface
     */
    private $salesOrderImportState;

    /**
     * @param Registry $coreRegistry
     * @param MarketplaceOrderRepositoryInterface $marketplaceOrderRepository
     * @param SalesOrderImporterInterface $salesOrderImporter
     * @param SalesOrderImportStateInterface|null $salesOrderImportState
     */
    public function __construct(
        Registry $coreRegistry,
        MarketplaceOrderRepositoryInterface $marketplaceOrderRepository,
        SalesOrderImporterInterface $salesOrderImporter,
        ?SalesOrderImportStateInterface $salesOrderImportState = null
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->marketplaceOrderRepository = $marketplaceOrderRepository;
        $this->salesOrderImporter = $salesOrderImporter;
        $this->salesOrderImportState = $salesOrderImportState
            ?? ObjectManager::getInstance()->get(SalesOrderImportStateInterface::class);
    }

    /**
     * @param SourceDeductionProcessor $subject
     * @param callable $proceed
     * @param EventObserver $observer
     */
    public function aroundExecute(SourceDeductionProcessor $subject, callable $proceed, EventObserver $observer)
    {
        /** @var Shipment $shipment */
        $shipment = $observer->getEvent()->getData(static::EVENT_KEY_SHIPMENT);
        $marketplaceOrder = null;

        try {
            $salesOrderIncrementId = $shipment->getOrder()->getIncrementId();

            $importedSalesOrderIncrementId = $this->coreRegistry->registry(
                BeforeQuoteSubmitObserver::REGISTRY_KEY_IMPORTED_SALES_ORDER_INCREMENT_ID
            );

            if (
                !empty($salesOrderIncrementId)
                && ($salesOrderIncrementId === $importedSalesOrderIncrementId)
            ) {
                $marketplaceOrder = $this->salesOrderImportState->getCurrentlyImportedMarketplaceOrder();
            }
        } catch (\Exception $e) {
            // The order has not been created yet.
        }

        if (!$marketplaceOrder) {
            try {
                if ($salesOrderId = (int) $shipment->getOrderId()) {
                    $marketplaceOrder = $this->marketplaceOrderRepository->getBySalesOrderId($salesOrderId);
                }
            } catch (\Exception $e) {
                // Not a marketplace order.
            }
        }

        if (!$marketplaceOrder || !$marketplaceOrder->isFulfilled()) {
            $proceed($observer);
        }
    }
}
