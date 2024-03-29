<?php

namespace ShoppingFeed\Manager\Console\Command\Orders;

use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SendStateUpdatesCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('shoppingfeed:orders:send-state-updates');
        $this->setDescription('Sends order state updates to Shopping Feed for one or more accounts');

        $this->setDefinition(
            [
                $this->getAccountsOption('Only send state updates for these account IDs'),
                $this->getStoresOption('Only send state updates for these account IDs'),
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

            $io->title('Sending order state updates for account IDs: ' . implode(', ', $storeIds));
            $io->progressStart(count($storeIds));

            foreach ($storeCollection as $store) {
                $this->marketplaceOrderManager->notifyStoreOrderUpdates($store);
                $io->progressAdvance(1);
            }

            $io->newLine(2);
            $io->success('Successfully sent order state updates.');
            $io->progressFinish();
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}
