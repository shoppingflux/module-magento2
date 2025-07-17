<?php

namespace ShoppingFeed\Manager\Model\Command\Orders;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use ShoppingFeed\Manager\Model\AbstractCommand;
use ShoppingFeed\Manager\Model\Command\ConfigInterface;
use ShoppingFeed\Manager\Model\Marketplace\Order\Manager as MarketplaceOrderManager;
use ShoppingFeed\Manager\Model\Marketplace\Order\Notification\UnreadLogs;
use ShoppingFeed\Manager\Model\Marketplace\Order\Notification\UnimportedOrders;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as SalesOrderImporterInterface;
use ShoppingFeed\Manager\Model\Sales\Order\SyncerInterface as SalesOrderSyncerInterface;

class ImportSalesOrders extends AbstractCommand
{
    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var MarketplaceOrderManager
     */
    private $marketplaceOrderManager;

    /**
     * @var SalesOrderImporterInterface
     */
    private $salesOrderImporter;

    /**
     * @var SalesOrderSyncerInterface
     */
    private $salesOrderSyncer;

    /**
     * @param ConfigInterface $config
     * @param MarketplaceOrderManager $marketplaceOrderManager
     * @param SalesOrderImporterInterface $salesOrderImporter
     * @param SalesOrderSyncerInterface $salesOrderSyncer
     */
    public function __construct(
        ConfigInterface $config,
        MarketplaceOrderManager $marketplaceOrderManager,
        SalesOrderImporterInterface $salesOrderImporter,
        SalesOrderSyncerInterface $salesOrderSyncer,
        ?CacheInterface $cache = null
    ) {
        $this->cache = $cache ?? ObjectManager::getInstance()->get(CacheInterface::class);
        $this->marketplaceOrderManager = $marketplaceOrderManager;
        $this->salesOrderImporter = $salesOrderImporter;
        $this->salesOrderSyncer = $salesOrderSyncer;
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

            $syncableOrders = $this->marketplaceOrderManager->getStoreSyncableOrders($store);
            $this->salesOrderSyncer->synchronizeStoreOrders($syncableOrders, $store);
        }

        $this->cache->remove(UnreadLogs::CACHE_KEY);
        $this->cache->remove(UnimportedOrders::CACHE_KEY);
    }
}
