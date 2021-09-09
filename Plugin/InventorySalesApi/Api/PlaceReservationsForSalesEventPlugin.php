<?php

namespace ShoppingFeed\Manager\Plugin\InventorySalesApi\Api;

use Magento\Framework\Api\AbstractSimpleObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesEventInterface;
use Magento\InventorySalesApi\Api\PlaceReservationsForSalesEventInterface;
use ShoppingFeed\Manager\Api\Marketplace\OrderRepositoryInterface as MarketplaceOrderRepositoryInterface;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as SalesOrderImporterInterface;
use ShoppingFeed\Manager\Observer\BeforeQuoteSubmitObserver;

class PlaceReservationsForSalesEventPlugin
{
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
     * @param MarketplaceOrderRepositoryInterface $marketplaceOrderRepository
     * @param SalesOrderImporterInterface $salesOrderImporter
     */
    public function __construct(
        Registry $coreRegistry,
        MarketplaceOrderRepositoryInterface $marketplaceOrderRepository,
        SalesOrderImporterInterface $salesOrderImporter
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->marketplaceOrderRepository = $marketplaceOrderRepository;
        $this->salesOrderImporter = $salesOrderImporter;
    }

    /**
     * @param PlaceReservationsForSalesEventInterface $subject
     * @param callable $proceed
     * @param array $items
     * @param SalesChannelInterface $salesChannel
     * @param SalesEventInterface $salesEvent
     */
    public function aroundExecute(
        PlaceReservationsForSalesEventInterface $subject,
        callable $proceed,
        array $items,
        SalesChannelInterface $salesChannel,
        SalesEventInterface $salesEvent
    ) {
        $marketplaceOrder = null;

        if ($salesEvent->getObjectType() === SalesEventInterface::OBJECT_TYPE_ORDER) {
            try {
                if (
                    ($salesEventAttributes = $salesEvent->getExtensionAttributes())
                    && ($salesEventAttributes instanceof AbstractSimpleObject)
                ) {
                    $salesEventAttributeData = $salesEventAttributes->__toArray();
                    $salesOrderIncrementId = $salesEventAttributeData['objectIncrementId'] ?? null;

                    $importedSalesOrderIncrementId = $this->coreRegistry->registry(
                        BeforeQuoteSubmitObserver::REGISTRY_KEY_IMPORTED_SALES_ORDER_INCREMENT_ID
                    );

                    if (
                        empty($salesOrderIncrementId)
                        || ($salesOrderIncrementId === $importedSalesOrderIncrementId)
                    ) {
                        $marketplaceOrder = $this->salesOrderImporter->getCurrentlyImportedMarketplaceOrder();
                    }
                }

                if (
                    !$marketplaceOrder
                    && ($salesOrderId = (int) $salesEvent->getObjectId())
                ) {
                    $marketplaceOrder = $this->marketplaceOrderRepository->getBySalesOrderId($salesOrderId);
                }
            } catch (\Exception $e) {
                // Not a marketplace order.
            }
        }

        if (!$marketplaceOrder || !$marketplaceOrder->isFulfilled()) {
            $proceed($items, $salesChannel, $salesEvent);
        }
    }
}
