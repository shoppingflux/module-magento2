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
        $this->setDescription('Fetches the new marketplace orders for one or more accounts');

        $this->setDefinition(
            [
                $this->getAccountsOption('Only fetch orders for these account IDs'),
                $this->getStoresOption('Only fetch orders for these account IDs'),
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

            $io->title('Fetching new marketplace orders for account IDs: ' . implode(', ', $storeIds));
            $io->progressStart(count($storeIds));

            foreach ($storeCollection as $store) {
                $importableOrders = $this->marketplaceOrderManager->getStoreImportableApiOrders($store);
                $this->marketplaceOrderImporter->importStoreOrders($importableOrders, $store, false);
                $io->progressAdvance(1);
            }

            $io->newLine(2);
            $io->success('Successfully fetched new marketplace orders.');
            $io->progressFinish();

            $io->title('Fetching synchronizable marketplace orders for account IDs: ' . implode(', ', $storeIds));
            $io->progressStart(count($storeIds));

            foreach ($storeCollection as $store) {
                $syncableOrders = $this->marketplaceOrderManager->getStoreSyncableApiOrders($store);
                $this->marketplaceOrderImporter->importStoreOrders($syncableOrders, $store, true);
                $io->progressAdvance(1);
            }

            $io->newLine(2);
            $io->success('Successfully fetched synchronizable marketplace orders.');
            $io->progressFinish();
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}
