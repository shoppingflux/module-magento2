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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $storeCollection = $this->getStoresOptionCollection($input);
            $storeIds = $storeCollection->getAllIds();

            $io->title('Fetching new marketplace orders for store IDs: ' . implode(', ', $storeIds));
            $io->progressStart(count($storeIds));

            foreach ($storeCollection as $store) {
                $importableOrders = $this->marketplaceOrderManager->getStoreImportableApiOrders($store);
                $this->marketplaceOrderImporter->importStoreOrders($importableOrders, $store);
                $io->progressAdvance(1);
            }
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}
