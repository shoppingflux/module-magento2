<?php

namespace ShoppingFeed\Manager\Console\Command\Orders;

use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class FetchMarketplaceOrdersCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('shoppingfeed:orders:fetch-marketplace-orders');
        $this->setDescription('Fetches the new marketplace orders of one or more stores');
        $this->setDefinition([ $this->getStoresOption('Only fetch orders for those store IDs') ]);
        parent::configure();
    }

    protected function executeActions(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $storeCollection = $this->getStoresOptionCollection($input);
            $storeIds = $storeCollection->getLoadedIds();

            $io->progressStart(2 * count($storeIds));

            $io->title('Fetching new marketplace orders for store IDs: ' . implode(', ', $storeIds));

            foreach ($storeCollection as $store) {
                $importableOrders = $this->marketplaceOrderManager->getStoreImportableApiOrders($store);
                $this->marketplaceOrderImporter->importStoreOrders($importableOrders, $store, false);
                $io->progressAdvance(1);
            }

            $io->title('Fetching synchronizable marketplace orders for store IDs: ' . implode(', ', $storeIds));

            foreach ($storeCollection as $store) {
                $syncableOrders = $this->marketplaceOrderManager->getStoreSyncableApiOrders($store);
                $this->marketplaceOrderImporter->importStoreOrders($syncableOrders, $store, true);
                $io->progressAdvance(1);
            }
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}
