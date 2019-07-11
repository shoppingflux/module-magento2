<?php

namespace ShoppingFeed\Manager\Plugin\CatalogInventory\Spi;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Model\Spi\StockStateProviderInterface;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use ShoppingFeed\Manager\Model\Sales\Order\ConfigInterface as OrderConfigInterface;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as OrderImporterInterface;

class StockStateProviderPlugin
{
    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var OrderConfigInterface
     */
    private $orderGeneralConfig;

    /**
     * @var OrderImporterInterface
     */
    private $orderImporter;

    /**
     * @param DataObjectFactory $dataObjectFactory
     * @param OrderConfigInterface $orderGeneralConfig
     * @param OrderImporterInterface $orderImporter
     */
    public function __construct(
        DataObjectFactory $dataObjectFactory,
        OrderConfigInterface $orderGeneralConfig,
        OrderImporterInterface $orderImporter
    ) {
        $this->dataObjectFactory = $dataObjectFactory;
        $this->orderGeneralConfig = $orderGeneralConfig;
        $this->orderImporter = $orderImporter;
    }

    /**
     * @param StockStateProviderInterface $subject
     * @param callable $proceed
     * @param StockItemInterface $stockItem
     * @param float $itemQty
     * @param float $qtyToCheck
     * @param float $origQty
     * @return DataObject
     */
    public function aroundCheckQuoteItemQty(
        StockStateProviderInterface $subject,
        callable $proceed,
        StockItemInterface $stockItem,
        $itemQty,
        $qtyToCheck,
        $origQty
    ) {
        if (!$this->orderImporter->isImportRunning()
            || (!$store = $this->orderImporter->getImportRunningForStore())
            || $this->orderGeneralConfig->shouldCheckProductAvailabilityAndOptions($store)
        ) {
            return $proceed($stockItem, $itemQty, $qtyToCheck, $origQty);
        }

        return $this->dataObjectFactory->create();
    }
}
