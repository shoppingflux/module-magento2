<?php

namespace ShoppingFeed\Manager\Console\Command\Orders;

use Magento\Framework\App\State as AppState;
use Magento\Framework\Config\ScopeInterface as ConfigScopeInterface;
use ShoppingFeed\Manager\Console\AbstractCommand as BaseCommand;
use ShoppingFeed\Manager\Model\Marketplace\Order\Importer as MarketplaceOrderImporter;
use ShoppingFeed\Manager\Model\Marketplace\Order\Manager as MarketplaceOrderManager;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\CollectionFactory as StoreCollectionFactory;
use ShoppingFeed\Manager\Model\Sales\Order\Importer as SalesOrderImporter;

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
     * @var SalesOrderImporter
     */
    protected $salesOrderImporter;

    /**
     * @param AppState $appState
     * @param ConfigScopeInterface $configScope
     * @param StoreCollectionFactory $storeCollectionFactory
     * @param MarketplaceOrderManager $marketplaceOrderManager
     * @param MarketplaceOrderImporter $marketplaceOrderImporter
     * @param SalesOrderImporter $salesOrderImporter
     */
    public function __construct(
        AppState $appState,
        ConfigScopeInterface $configScope,
        StoreCollectionFactory $storeCollectionFactory,
        MarketplaceOrderManager $marketplaceOrderManager,
        MarketplaceOrderImporter $marketplaceOrderImporter,
        SalesOrderImporter $salesOrderImporter
    ) {
        $this->marketplaceOrderManager = $marketplaceOrderManager;
        $this->marketplaceOrderImporter = $marketplaceOrderImporter;
        $this->salesOrderImporter = $salesOrderImporter;
        parent::__construct($appState, $configScope, $storeCollectionFactory);
    }
}
