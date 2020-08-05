<?php

namespace ShoppingFeed\Manager\Console\Command\Orders;

use Magento\Framework\App\State as AppState;
use Magento\Framework\Config\ScopeInterface as ConfigScopeInterface;
use ShoppingFeed\Manager\Console\AbstractCommand as BaseCommand;
use ShoppingFeed\Manager\Model\Marketplace\Order\Importer as MarketplaceOrderImporter;
use ShoppingFeed\Manager\Model\Marketplace\Order\Manager as MarketplaceOrderManager;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\CollectionFactory as StoreCollectionFactory;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as SalesOrderImporterInterface;
use ShoppingFeed\Manager\Model\Sales\Order\SyncerInterface as SalesOrderSyncerInterface;

abstract class AbstractCommand extends BaseCommand
{
    /**
     * @var MarketplaceOrderManager
     */
    protected $marketplaceOrderManager;

    /**
     * @var MarketplaceOrderImporter
     */
    protected $marketplaceOrderImporter;

    /**
     * @var SalesOrderImporterInterface
     */
    protected $salesOrderImporter;

    /**
     * @var SalesOrderSyncerInterface
     */
    protected $salesOrderSyncer;

    /**
     * @param AppState $appState
     * @param ConfigScopeInterface $configScope
     * @param StoreCollectionFactory $storeCollectionFactory
     * @param MarketplaceOrderManager $marketplaceOrderManager
     * @param MarketplaceOrderImporter $marketplaceOrderImporter
     * @param SalesOrderImporterInterface $salesOrderImporter
     * @param SalesOrderSyncerInterface $salesOrderSyncer
     */
    public function __construct(
        AppState $appState,
        ConfigScopeInterface $configScope,
        StoreCollectionFactory $storeCollectionFactory,
        MarketplaceOrderManager $marketplaceOrderManager,
        MarketplaceOrderImporter $marketplaceOrderImporter,
        SalesOrderImporterInterface $salesOrderImporter,
        SalesOrderSyncerInterface $salesOrderSyncer
    ) {
        $this->marketplaceOrderManager = $marketplaceOrderManager;
        $this->marketplaceOrderImporter = $marketplaceOrderImporter;
        $this->salesOrderImporter = $salesOrderImporter;
        $this->salesOrderSyncer = $salesOrderSyncer;
        parent::__construct($appState, $configScope, $storeCollectionFactory);
    }
}
