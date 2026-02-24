<?php

namespace ShoppingFeed\Manager\Plugin\InventorySales;

use Magento\Framework\App\ObjectManager;
use Magento\InventorySales\Model\CheckItemsQuantity;
use ShoppingFeed\Manager\Model\Sales\Order\ConfigInterface as OrderConfigInterface;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as OrderImporterInterface;
use ShoppingFeed\Manager\Model\Sales\Order\ImportStateInterface as SalesOrderImportStateInterface;

class CheckItemsQuantityPlugin
{
    /**
     * @var OrderConfigInterface
     */
    private $orderGeneralConfig;

    /**
     * @var OrderImporterInterface
     */
    private $orderImporter;

    /**
     * @var SalesOrderImportStateInterface
     */
    private $salesOrderImportState;

    /**
     * @param OrderConfigInterface $orderGeneralConfig
     * @param OrderImporterInterface $orderImporter
     * @param SalesOrderImportStateInterface|null $salesOrderImportState
     */
    public function __construct(
        OrderConfigInterface $orderGeneralConfig,
        OrderImporterInterface $orderImporter,
        ?SalesOrderImportStateInterface $salesOrderImportState = null
    ) {
        $this->orderGeneralConfig = $orderGeneralConfig;
        $this->orderImporter = $orderImporter;
        $this->salesOrderImportState = $salesOrderImportState
            ?? ObjectManager::getInstance()->get(SalesOrderImportStateInterface::class);
    }

    /**
     * @param CheckItemsQuantity $subject
     * @param callable $proceed
     * @param array $items
     * @param int $stockId
     */
    public function aroundExecute(CheckItemsQuantity $subject, callable $proceed, array $items, $stockId)
    {
        if (
            !$this->salesOrderImportState->isImportRunning()
            || (!$store = $this->salesOrderImportState->getImportRunningForStore())
            || $this->orderGeneralConfig->shouldCheckProductAvailabilityAndOptions($store)
        ) {
            $proceed($items, $stockId);
        }
    }
}
