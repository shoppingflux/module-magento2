<?php

namespace ShoppingFeed\Manager\Console\Command\Orders;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Config\ScopeInterface as ConfigScopeInterface;
use Magento\Framework\Console\Cli;
use ShoppingFeed\Manager\Model\Marketplace\Order\Importer as MarketplaceOrderImporter;
use ShoppingFeed\Manager\Model\Marketplace\Order\Manager as MarketplaceOrderManager;
use ShoppingFeed\Manager\Model\Marketplace\Order\Notification\UnimportedOrders;
use ShoppingFeed\Manager\Model\Marketplace\Order\Notification\UnreadLogs;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\CollectionFactory as StoreCollectionFactory;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as SalesOrderImporterInterface;
use ShoppingFeed\Manager\Model\Sales\Order\SyncerInterface as SalesOrderSyncerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportSalesOrdersCommand extends AbstractCommand
{
    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @param AppState $appState
     * @param ConfigScopeInterface $configScope
     * @param StoreCollectionFactory $storeCollectionFactory
     * @param MarketplaceOrderManager $marketplaceOrderManager
     * @param MarketplaceOrderImporter $marketplaceOrderImporter
     * @param SalesOrderImporterInterface $salesOrderImporter
     * @param SalesOrderSyncerInterface $salesOrderSyncer
     * @param CacheInterface|null $cache
     */
    public function __construct(
        AppState $appState,
        ConfigScopeInterface $configScope,
        StoreCollectionFactory $storeCollectionFactory,
        MarketplaceOrderManager $marketplaceOrderManager,
        MarketplaceOrderImporter $marketplaceOrderImporter,
        SalesOrderImporterInterface $salesOrderImporter,
        SalesOrderSyncerInterface $salesOrderSyncer,
        CacheInterface $cache = null
    ) {
        $this->cache = $cache ?? ObjectManager::getInstance()->get(CacheInterface::class);

        parent::__construct(
            $appState,
            $configScope,
            $storeCollectionFactory,
            $marketplaceOrderManager,
            $marketplaceOrderImporter,
            $salesOrderImporter,
            $salesOrderSyncer
        );
    }

    protected function configure()
    {
        $this->setName('shoppingfeed:orders:import-sales-orders');
        $this->setDescription('Imports the pending marketplace orders for one or more accounts');

        $this->setDefinition(
            [
                $this->getAccountsOption('Only import orders for these account IDs'),
                $this->getStoresOption('Only import orders for these account IDs'),
            ]
        );

        parent::configure();
    }

    protected function executeActions(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $storeCollection = $this->getStoresOptionCollection($input);
            $storeIds = $storeCollection->getLoadedIds();

            $io->title('Importing marketplace orders for account IDs: ' . implode(', ', $storeIds));
            $io->progressStart(count($storeIds));

            foreach ($storeCollection as $store) {
                $importableOrders = $this->marketplaceOrderManager->getStoreImportableOrders($store);
                $this->salesOrderImporter->importStoreOrders($importableOrders, $store);
                $io->progressAdvance(1);
            }

            $io->newLine(2);
            $io->success('Successfully imported marketplace orders.');
            $io->progressFinish();

            $io->title('Synchronizing imported orders for account IDs: ' . implode(', ', $storeIds));
            $io->progressStart(count($storeIds));

            foreach ($storeCollection as $store) {
                $syncableOrders = $this->marketplaceOrderManager->getStoreSyncableOrders($store);
                $this->salesOrderSyncer->synchronizeStoreOrders($syncableOrders, $store);
                $io->progressAdvance(1);
            }

            $io->newLine(2);
            $io->success('Successfully synchronized imported orders.');
            $io->progressFinish();

            $this->cache->remove(UnreadLogs::CACHE_KEY);
            $this->cache->remove(UnimportedOrders::CACHE_KEY);
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}

