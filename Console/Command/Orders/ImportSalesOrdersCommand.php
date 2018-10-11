<?php

namespace ShoppingFeed\Manager\Console\Command\Orders;

use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportSalesOrdersCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('shoppingfeed:orders:import-sales-orders');
        $this->setDescription('Imports the pending marketplace orders of one or more stores');
        $this->setDefinition([ $this->getStoresOption('Only import orders for those store IDs') ]);
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $storeCollection = $this->getStoresOptionCollection($input);
            $storeIds = $storeCollection->getLoadedIds();

            $io->title('Importing marketplace orders for store IDs: ' . implode(', ', $storeIds));
            $io->progressStart(count($storeIds));

            foreach ($storeCollection as $store) {
                $marketplaceOrders = $this->marketplaceOrderManager->getStoreImportableOrders($store);
                $this->salesOrderImporter->importStoreOrders($marketplaceOrders, $store);
                $io->progressAdvance(1);
            }
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}

