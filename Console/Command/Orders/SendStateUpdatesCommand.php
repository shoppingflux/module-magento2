<?php

namespace ShoppingFeed\Manager\Console\Command\Orders;

use Magento\Framework\App\State as AppState;
use Magento\Framework\Console\Cli;
use Magento\Sales\Api\Data\ShipmentInterface as SalesShipmentInterface;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as SalesShipmentCollectionFactory;
use ShoppingFeed\Manager\Model\Marketplace\Order as MarketplaceOrder;
use ShoppingFeed\Manager\Model\Marketplace\Order\Importer as MarketplaceOrderImporter;
use ShoppingFeed\Manager\Model\Marketplace\Order\Manager as MarketplaceOrderManager;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\CollectionFactory as StoreCollectionFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Collection as MarketplaceOrderCollection;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\CollectionFactory as MarketplaceOrderCollectionFactory;
use ShoppingFeed\Manager\Model\Sales\Order\Importer as SalesOrderImporter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


class SendStateUpdatesCommand extends AbstractCommand
{
    /**
     * @var SalesShipmentCollectionFactory
     */
    private $salesShipmentCollectionFactory;

    /**
     * @param AppState $appState
     * @param StoreCollectionFactory $storeCollectionFactory
     * @param MarketplaceOrderCollectionFactory $marketplaceOrderCollectionFactory
     * @param MarketplaceOrderManager $marketplaceOrderManager
     * @param MarketplaceOrderImporter $marketplaceOrderImporter
     * @param SalesOrderImporter $salesOrderImporter
     * @param SalesShipmentCollectionFactory $salesShipmentCollectionFactory
     */
    public function __construct(
        AppState $appState,
        StoreCollectionFactory $storeCollectionFactory,
        MarketplaceOrderCollectionFactory $marketplaceOrderCollectionFactory,
        MarketplaceOrderManager $marketplaceOrderManager,
        MarketplaceOrderImporter $marketplaceOrderImporter,
        SalesOrderImporter $salesOrderImporter,
        SalesShipmentCollectionFactory $salesShipmentCollectionFactory
    ) {
        $this->salesShipmentCollectionFactory = $salesShipmentCollectionFactory;

        parent::__construct(
            $appState,
            $storeCollectionFactory,
            $marketplaceOrderCollectionFactory,
            $marketplaceOrderManager,
            $marketplaceOrderImporter,
            $salesOrderImporter
        );
    }

    protected function configure()
    {
        $this->setName('shoppingfeed:orders:send-state-updates');
        $this->setDescription('Send order state updates to Shopping Feed for one or more stores');
        $this->setDefinition([ $this->getStoresOption('Only send state updates for those store IDs') ]);
        parent::configure();
    }

    /**
     * @param int[] $orderIds
     * @return SalesShipmentInterface[]
     */
    private function getSalesOrderShipmentsByOrder(array $orderIds)
    {
        $shipmentCollection = $this->salesShipmentCollectionFactory->create();
        $shipmentCollection->addFieldToFilter(SalesShipmentInterface::ORDER_ID, [ 'in' => $orderIds ]);
        $orderShipments = [];

        /** @var SalesShipmentInterface $shipment */
        foreach ($shipmentCollection as $shipment) {
            $orderShipments[$shipment->getOrderId()][] = $shipment;
        }

        return $orderShipments;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $storeCollection = $this->getStoresOptionCollection($input);
            $storeIds = $storeCollection->getAllIds();

            $io->title('Synchronizing the order state updates for store IDs: ' . implode(', ', $storeIds));
            $io->progressStart(count($storeIds));

            foreach ($storeCollection as $store) {
                $importedCollection = $this->marketplaceOrderCollectionFactory->create();
                $importedCollection->addStoreIdFilter($store->getId());
                $importedCollection->addNotifiableImportFilter();

                /** @var MarketplaceOrder $marketplaceOrder */
                foreach ($importedCollection as $marketplaceOrder) {
                    $this->marketplaceOrderManager->notifyOrderImportSuccess(
                        $marketplaceOrder,
                        $marketplaceOrder->getData(MarketplaceOrderCollection::KEY_SALES_INCREMENT_ID),
                        $store
                    );
                }

                $cancelledCollection = $this->marketplaceOrderCollectionFactory->create();
                $cancelledCollection->addStoreIdFilter($store->getId());
                $cancelledCollection->addNotifiableCancellationFilter();

                foreach ($importedCollection as $marketplaceOrder) {
                    $this->marketplaceOrderManager->notifyOrderCancellation($marketplaceOrder, $store);
                }

                $shippedCollection = $this->marketplaceOrderCollectionFactory->create();
                $shippedCollection->addStoreIdFilter($store->getId());
                $shippedCollection->addNotifiableShipmentFilter();
                $shippedSalesOrderIds = [];

                foreach ($shippedCollection as $marketplaceOrder) {
                    $shippedSalesOrderIds[] = (int) $marketplaceOrder->getSalesOrderId();
                }

                $salesOrderShipments = $this->getSalesOrderShipmentsByOrder($shippedSalesOrderIds);

                foreach ($shippedCollection as $marketplaceOrder) {
                    $salesOrderId = (int) $marketplaceOrder->getSalesOrderId();

                    if (isset($salesOrderShipments[$salesOrderId])) {
                        $carrierTitle = null;
                        $trackingNumber = null;
                        $trackingUrl = null;

                        /** @var SalesShipmentInterface $orderShipment */
                        foreach ($salesOrderShipments[$salesOrderId] as $orderShipment) {
                            foreach ($orderShipment->getTracks() as $shipmentTrack) {
                                $carrierTitle = trim($shipmentTrack->getTitle());
                                $trackingNumber = trim($shipmentTrack->getTrackNumber());
                                $trackingUrl = is_callable([ $shipmentTrack, 'getUrl' ])
                                    ? trim($shipmentTrack->getUrl())
                                    : '';

                                if (!empty($carrier) && !empty($trackingNumber)) {
                                    break 2;
                                }
                            }
                        }

                        $this->marketplaceOrderManager->notifyOrderShipment(
                            $marketplaceOrder,
                            $carrierTitle,
                            $trackingNumber,
                            $trackingUrl,
                            $store
                        );
                    }
                }

                $io->progressAdvance(1);
            }
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}
