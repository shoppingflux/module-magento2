<?php

namespace ShoppingFeed\Manager\Model\Command\Orders;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Model\AbstractCommand;
use ShoppingFeed\Manager\Model\Command\ConfigInterface;
use ShoppingFeed\Manager\Model\Marketplace\Order\Manager as MarketplaceOrderManager;
use ShoppingFeed\Manager\Model\Sales\Order\Importer as SalesOrderImporter;

class ImportSalesOrders extends AbstractCommand
{
    /**
     * @var MarketplaceOrderManager
     */
    private $marketplaceOrderManager;

    /**
     * @var SalesOrderImporter
     */
    private $salesOrderImporter;

    /**
     * @param ConfigInterface $config
     * @param MarketplaceOrderManager $marketplaceOrderManager
     * @param SalesOrderImporter $salesOrderImporter
     */
    public function __construct(
        ConfigInterface $config,
        MarketplaceOrderManager $marketplaceOrderManager,
        SalesOrderImporter $salesOrderImporter
    ) {
        $this->marketplaceOrderManager = $marketplaceOrderManager;
        $this->salesOrderImporter = $salesOrderImporter;
        parent::__construct($config);
    }

    public function getLabel()
    {
        return __('Import Orders');
    }

    /**
     * @param DataObject $configData
     * @throws \Exception
     */
    public function run(DataObject $configData)
    {
        foreach ($this->getConfig()->getStores($configData) as $store) {
            $importableOrders = $this->marketplaceOrderManager->getStoreImportableOrders($store);
            $this->salesOrderImporter->importStoreOrders($importableOrders, $store);
        }
    }
}
