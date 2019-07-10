<?php

namespace ShoppingFeed\Manager\Plugin\InventorySales;

use Magento\InventorySales\Model\CheckItemsQuantity;
use ShoppingFeed\Manager\Model\Sales\Order\ConfigInterface as OrderConfigInterface;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as OrderImporterInterface;

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
     * @param OrderConfigInterface $orderGeneralConfig
     * @param OrderImporterInterface $orderImporter
     */
    public function __construct(OrderConfigInterface $orderGeneralConfig, OrderImporterInterface $orderImporter)
    {
        $this->orderGeneralConfig = $orderGeneralConfig;
        $this->orderImporter = $orderImporter;
    }

    /**
     * @param CheckItemsQuantity $subject
     * @param callable $proceed
     * @param array $items
     * @param int $stockId
     */
    public function aroundExecute(CheckItemsQuantity $subject, callable $proceed, array $items, $stockId)
    {
        if (!$this->orderImporter->isImportRunning()
            || (!$store = $this->orderImporter->getImportRunningForStore())
            || $this->orderGeneralConfig->shouldCheckProductAvailabilityAndOptions($store)
        ) {
            $proceed($items, $stockId);
        }
    }
}
