<?php

namespace ShoppingFeed\Manager\Model\Marketplace\Order;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\LogInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\TicketInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\LogInterfaceFactory as OrderLogInterfaceFactory;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\TicketInterfaceFactory as OrderTicketInterfaceFactory;
use ShoppingFeed\Manager\Api\Marketplace\Order\LogRepositoryInterface as OrderLogRepositoryInterface;
use ShoppingFeed\Manager\Api\Marketplace\Order\TicketRepositoryInterface as OrderTicketRepositoryInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Collection as OrderCollection;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\CollectionFactory as OrderCollectionFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Ticket\CollectionFactory as OrderTicketCollectionFactory;
use ShoppingFeed\Manager\Model\Sales\Order\ConfigInterface as OrderConfigInterface;
use ShoppingFeed\Manager\Model\Sales\Order\SyncerInterface as SalesOrderSyncerInterface;
use ShoppingFeed\Manager\Model\Sales\Order\Shipment\Track as ShipmentTrack;
use ShoppingFeed\Manager\Model\Sales\Order\Shipment\Track\Collector as SalesShipmentTrackCollector;
use ShoppingFeed\Manager\Model\ShoppingFeed\Api\SessionManager as ApiSessionManager;
use ShoppingFeed\Sdk\Api\Order\OrderOperation as ApiOrderOperation;
use ShoppingFeed\Sdk\Api\Order\OrderResource as ApiOrder;
use ShoppingFeed\Sdk\Api\Task\TicketResource as ApiTicket;

class Manager
{
    const API_FILTER_ACKNOWLEDGEMENT = 'acknowledgment';
    const API_FILTER_CHANNEL_ID = 'channelId';
    const API_FILTER_REFERENCE = 'reference';
    const API_FILTER_SINCE = 'since';
    const API_FILTER_STATUS = 'status';

    const API_ACKNOWLEDGEMENT_STATUS_SUCCESS = 'success';
    const API_ACKNOWLEDGEMENT_STATUS_FAILURE = 'error';

    const API_ACKNOWLEDGED = 'acknowledged';
    const API_UNACKNOWLEDGED = 'unacknowledged';

    /**
     * @var ApiSessionManager
     */
    private $apiSessionManager;

    /**
     * @var OrderConfigInterface
     */
    private $orderGeneralConfig;

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var OrderLogInterfaceFactory
     */
    private $orderLogFactory;

    /**
     * @var OrderLogRepositoryInterface
     */
    private $orderLogRepository;

    /**
     * @var OrderTicketInterfaceFactory
     */
    private $orderTicketFactory;

    /**
     * @var OrderTicketRepositoryInterface
     */
    private $orderTicketRepository;

    /**
     * @var OrderTicketCollectionFactory
     */
    private $orderTicketCollectionFactory;

    /**
     * @var SalesShipmentTrackCollector
     */
    private $salesShipmentTrackCollector;

    /**
     * @var int
     */
    private $notificationSliceSize = 50;

    /**
     * @var int
     */
    private $maxNotificationIterations = 20;

    /**
     * @param ApiSessionManager $apiSessionManager
     * @param OrderConfigInterface $orderGeneralConfig
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param OrderLogInterfaceFactory $orderLogFactory
     * @param OrderLogRepositoryInterface $orderLogRepository
     * @param OrderTicketInterfaceFactory $orderTicketFactory
     * @param OrderTicketRepositoryInterface $orderTicketRepository
     * @param SalesShipmentTrackCollector $salesShipmentTrackCollector
     * @param int $notificationSliceSize
     * @param int $maxNotificationIterations
     * @param OrderTicketCollectionFactory|null $orderTicketCollectionFactory
     */
    public function __construct(
        ApiSessionManager $apiSessionManager,
        OrderConfigInterface $orderGeneralConfig,
        OrderCollectionFactory $orderCollectionFactory,
        OrderLogInterfaceFactory $orderLogFactory,
        OrderLogRepositoryInterface $orderLogRepository,
        OrderTicketInterfaceFactory $orderTicketFactory,
        OrderTicketRepositoryInterface $orderTicketRepository,
        SalesShipmentTrackCollector $salesShipmentTrackCollector,
        $notificationSliceSize = 50,
        $maxNotificationIterations = 20,
        OrderTicketCollectionFactory $orderTicketCollectionFactory = null
    ) {
        $this->apiSessionManager = $apiSessionManager;
        $this->orderGeneralConfig = $orderGeneralConfig;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderLogFactory = $orderLogFactory;
        $this->orderLogRepository = $orderLogRepository;
        $this->orderTicketFactory = $orderTicketFactory;
        $this->orderTicketRepository = $orderTicketRepository;
        $this->salesShipmentTrackCollector = $salesShipmentTrackCollector;
        $this->notificationSliceSize = (int) $notificationSliceSize;
        $this->maxNotificationIterations = (int) $maxNotificationIterations;
        $this->orderTicketCollectionFactory = $orderTicketCollectionFactory
            ?? ObjectManager::getInstance()->get(OrderTicketCollectionFactory::class);
    }

    /**
     * @param StoreInterface $store
     * @return string[]
     */
    public function getSyncableShoppingFeedStatuses(StoreInterface $store)
    {
        $statuses = [];

        $statusActions = [
            OrderInterface::STATUS_REFUSED => $this->orderGeneralConfig->getOrderRefusalSyncingAction($store),
            OrderInterface::STATUS_CANCELLED => $this->orderGeneralConfig->getOrderCancellationSyncingAction($store),
            OrderInterface::STATUS_REFUNDED => $this->orderGeneralConfig->getOrderRefundSyncingAction($store),
        ];

        foreach ($statusActions as $status => $action) {
            if (SalesOrderSyncerInterface::SYNCING_ACTION_NONE !== $action) {
                $statuses[] = $status;
            }
        }

        return $statuses;
    }

    /**
     * @param StoreInterface $store
     * @param int $channelId
     * @param string $reference
     * @return ApiOrder|null
     * @throws LocalizedException
     */
    public function getStoreImportableApiOrderByChannelAndReference(StoreInterface $store, $channelId, $reference)
    {
        if (empty($channelId) || empty($reference)) {
            return null;
        }

        $apiStore = $this->apiSessionManager->getStoreApiResource($store);

        $orders = $apiStore->getOrderApi()
            ->getAll(
                [
                    self::API_FILTER_ACKNOWLEDGEMENT => self::API_UNACKNOWLEDGED,
                    self::API_FILTER_CHANNEL_ID => (int) $channelId,
                    self::API_FILTER_REFERENCE => trim((string) $reference),
                ]
            );

        $singleOrder = null;

        foreach ($orders as $order) {
            if ($order->getReference() === $reference) {
                if (null === $singleOrder) {
                    $singleOrder = $order;
                } else {
                    $singleOrder = null;
                    break;
                }
            }
        }

        return $singleOrder;
    }

    /**
     * @param StoreInterface $store
     * @return ApiOrder[]
     * @throws LocalizedException
     */
    public function getStoreImportableApiOrders(StoreInterface $store)
    {
        if (!$this->orderGeneralConfig->shouldImportOrders($store)) {
            return [];
        }

        $apiStore = $this->apiSessionManager->getStoreApiResource($store);

        return $apiStore->getOrderApi()
            ->getAll(
                [
                    self::API_FILTER_ACKNOWLEDGEMENT => self::API_UNACKNOWLEDGED,
                    self::API_FILTER_SINCE => $this->orderGeneralConfig->getOrderImportFromDate($store),
                ]
            );
    }

    /**
     * @param StoreInterface $store
     * @param int|null $maximumCount
     * @return OrderInterface[]
     */
    public function getStoreImportableOrders(StoreInterface $store, $maximumCount = null)
    {
        if (!$this->orderGeneralConfig->shouldImportOrders($store)) {
            return [];
        }

        $orderCollection = $this->orderCollectionFactory->create();

        $orderCollection->addNonImportedFilter();
        $orderCollection->addImportableFilter();
        $orderCollection->addCreatedFromDateFilter($this->orderGeneralConfig->getOrderImportFromDate($store));
        $orderCollection->addStoreIdFilter($store->getId());

        if (null !== $maximumCount) {
            $orderCollection->setCurPage(1);
            $orderCollection->setPageSize($maximumCount);
        }

        $orderCollection->load();

        return $orderCollection->getItems();
    }

    /**
     * @param StoreInterface $store
     * @return ApiOrder[]
     * @throws LocalizedException
     */
    public function getStoreSyncableApiOrders(StoreInterface $store)
    {
        if (!$this->orderGeneralConfig->shouldImportOrders($store)) {
            return [];
        }

        $apiStore = $this->apiSessionManager->getStoreApiResource($store);
        $statuses = $this->getSyncableShoppingFeedStatuses($store);

        if (empty($statuses)) {
            return [];
        }

        return $apiStore->getOrderApi()
            ->getAll(
                [
                    self::API_FILTER_STATUS => $statuses,
                    self::API_FILTER_ACKNOWLEDGEMENT => self::API_ACKNOWLEDGED,
                    self::API_FILTER_SINCE => $this->orderGeneralConfig->getOrderSyncingFromDate($store),
                ]
            );
    }

    /**
     * @param StoreInterface $store
     * @param int|null $maximumCount
     * @return OrderInterface[]
     */
    public function getStoreSyncableOrders(StoreInterface $store, $maximumCount = null)
    {
        if (!$this->orderGeneralConfig->shouldImportOrders($store)) {
            return [];
        }

        $statuses = $this->getSyncableShoppingFeedStatuses($store);

        if (empty($statuses)) {
            return [];
        }

        $orderCollection = $this->orderCollectionFactory->create();

        $orderCollection->addImportedFilter();
        $orderCollection->addShoppingFeedStatusFilter($statuses);
        $orderCollection->addCreatedFromDateFilter($this->orderGeneralConfig->getOrderSyncingFromDate($store));
        $orderCollection->addStoreIdFilter($store->getId());

        if (null !== $maximumCount) {
            $orderCollection->setCurPage(1);
            $orderCollection->setPageSize($maximumCount);
        }

        $orderCollection->load();

        return $orderCollection->getItems();
    }

    /**
     * @param StoreInterface $store
     * @throws LocalizedException
     */
    private function refreshPendingTicketStatuses(StoreInterface $store)
    {
        $apiStore = $this->apiSessionManager->getStoreApiResource($store);
        $ticketApi = $apiStore->getTicketApi();

        $pendingTickets = $this->orderTicketCollectionFactory
            ->create()
            ->addStatusFilter(TicketInterface::STATUS_PENDING)
            ->setCurPage(1)
            ->setPageSize($this->notificationSliceSize);

        $sliceCount = 0;
        $handledTicketIds = [];

        do {
            $sliceTickets = clone $pendingTickets;
            $sliceTickets->clear();
            $sliceTickets->addExcludedIdFilter($handledTicketIds);

            /** @var TicketInterface $ticket */
            foreach ($sliceTickets as $ticket) {
                $apiTicket = null;
                $handledTicketIds[] = $ticket->getId();

                try {
                    if ($batchId = $ticket->getShoppingFeedBatchId()) {
                        foreach ($ticketApi->getByBatch($batchId) as $batchTicket) {
                            $apiTicket = $batchTicket;
                            break;
                        }
                    } elseif ($ticketId = $ticket->getShoppingFeedTicketId()) {
                        $apiTicket = $ticketApi->getOne($ticketId);
                    }

                    if (
                        ($apiTicket instanceof ApiTicket)
                        && !in_array($apiTicket->getStatus(), TicketInterface::API_PENDING_STATUSES, true)
                    ) {
                        $ticket->setStatus(
                            ($apiTicket->getStatus() === TicketInterface::API_STATUS_FAILED)
                                ? TicketInterface::STATUS_FAILED
                                : TicketInterface::STATUS_HANDLED
                        );

                        $this->orderTicketRepository->save($ticket);
                    }
                } catch (\Exception $e) {
                    // Ignore failures. We will retry them later.
                }
            }
        } while (($sliceTickets->count() > 0) && (++$sliceCount < $this->maxNotificationIterations));
    }

    /**
     * @param OrderInterface $order
     * @param ApiTicket $apiTicket
     * @param string $action
     * @throws CouldNotSaveException
     */
    private function registerOrderApiTicket(OrderInterface $order, ApiTicket $apiTicket, $action)
    {
        $ticket = $this->orderTicketFactory->create();
        $ticket->setShoppingFeedTicketId(trim((string) $apiTicket->getId()));
        $ticket->setOrderId($order->getId());
        $ticket->setAction($action);
        $ticket->setStatus(TicketInterface::STATUS_PENDING);
        $this->orderTicketRepository->save($ticket);
    }

    /**
     * @param OrderInterface $order
     * @param ApiTicket $apiTicket
     * @param string $action
     * @throws CouldNotSaveException
     */
    private function registerOrderApiTicketBatch(OrderInterface $order, string $batchId, $action)
    {
        $ticket = $this->orderTicketFactory->create();
        $ticket->setShoppingFeedBatchId($batchId);
        $ticket->setOrderId($order->getId());
        $ticket->setAction($action);
        $ticket->setStatus(TicketInterface::STATUS_PENDING);
        $this->orderTicketRepository->save($ticket);
    }

    /**
     * @param StoreInterface $store
     * @param OrderInterface $order
     * @param string $action
     * @param ApiOrderOperation $operation
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    private function executeApiOrderOperation(
        StoreInterface $store,
        OrderInterface $order,
        $action,
        ApiOrderOperation $operation
    ) {
        $result = $this->apiSessionManager
            ->getStoreApiResource($store)
            ->getOrderApi()
            ->execute($operation);

        $apiTickets = $result->getTickets();

        foreach ($apiTickets as $apiTicket) {
            $this->registerOrderApiTicket($order, $apiTicket, $action);

            return;
        }

        foreach ($result->getBatchIds() as $batchId) {
            $this->registerOrderApiTicketBatch($order, $batchId, $action);

            return;
        }
    }

    /**
     * @param OrderInterface $order
     * @param string $storeReference
     * @param string $action
     * @param StoreInterface $store
     * @throws \Exception
     */
    private function notifyStoreOrderImportResult(
        OrderInterface $order,
        $storeReference,
        $action,
        StoreInterface $store
    ) {
        $operation = new ApiOrderOperation();
        $reference = $order->getMarketplaceOrderNumber();
        $channelName = $order->getMarketplaceName();

        if (TicketInterface::ACTION_ACKNOWLEDGE_SUCCESS === $action) {
            $apiStatus = self::API_ACKNOWLEDGEMENT_STATUS_SUCCESS;
        } else {
            $apiStatus = self::API_ACKNOWLEDGEMENT_STATUS_FAILURE;
        }

        $operation->acknowledge($reference, $channelName, $storeReference, $apiStatus);

        $this->executeApiOrderOperation($store, $order, $action, $operation);
    }

    /**
     * @param OrderInterface $order
     * @param string $salesIncrementId
     * @param StoreInterface $store
     * @throws \Exception
     */
    public function notifyStoreOrderImportSuccess(OrderInterface $order, $salesIncrementId, StoreInterface $store)
    {
        $this->notifyStoreOrderImportResult(
            $order,
            $salesIncrementId,
            TicketInterface::ACTION_ACKNOWLEDGE_SUCCESS,
            $store
        );
    }

    /**
     * @param OrderInterface $order
     * @param StoreInterface $store
     * @throws \Exception
     */
    public function notifyStoreOrderImportFailure(OrderInterface $order, StoreInterface $store)
    {
        $this->notifyStoreOrderImportResult(
            $order,
            (string) $order->getId(),
            TicketInterface::ACTION_ACKNOWLEDGE_FAILURE,
            $store
        );
    }

    /**
     * @param StoreInterface $store
     * @throws \Exception
     */
    public function notifyStoreOrderImportUpdates(StoreInterface $store)
    {
        $importedCollection = $this->orderCollectionFactory->create();
        $importedCollection->addStoreIdFilter($store->getId());
        $importedCollection->addNotifiableImportFilter();
        $importedCollection->setCurPage(1);
        $importedCollection->setPageSize($this->notificationSliceSize);

        $sliceCount = 0;

        do {
            $importedCollection->clear();
            $hasNotifiedOrders = $importedCollection->count() > 0;

            foreach ($importedCollection as $order) {
                $this->notifyStoreOrderImportSuccess(
                    $order,
                    trim((string) $order->getDataByKey(OrderCollection::KEY_SALES_INCREMENT_ID)),
                    $store
                );
            }
        } while ($hasNotifiedOrders && (++$sliceCount < $this->maxNotificationIterations));
    }

    /**
     * @param OrderInterface $order
     * @param StoreInterface $store
     * @throws \Exception
     */
    public function notifyStoreOrderCancellation(OrderInterface $order, StoreInterface $store)
    {
        $operation = new ApiOrderOperation();
        $reference = $order->getMarketplaceOrderNumber();
        $channelName = $order->getMarketplaceName();

        $operation->cancel($reference, $channelName);

        $this->executeApiOrderOperation($store, $order, TicketInterface::ACTION_CANCEL, $operation);
    }

    /**
     * @param StoreInterface $store
     * @throws \Exception
     */
    public function notifyStoreOrderCancellationUpdates(StoreInterface $store)
    {
        $cancelledCollection = $this->orderCollectionFactory->create();
        $cancelledCollection->addStoreIdFilter($store->getId());
        $cancelledCollection->addNotifiableCancellationFilter();
        $cancelledCollection->setCurPage(1);
        $cancelledCollection->setPageSize($this->notificationSliceSize);

        $sliceCount = 0;

        do {
            $cancelledCollection->clear();
            $hasNotifiedOrders = $cancelledCollection->count() > 0;

            foreach ($cancelledCollection as $order) {
                $this->notifyStoreOrderCancellation($order, $store);
            }
        } while ($hasNotifiedOrders && (++$sliceCount < $this->maxNotificationIterations));
    }

    /**
     * @param OrderInterface $order
     * @param ShipmentTrack $shipmentTrack
     * @param StoreInterface $store
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    public function notifyStoreOrderShipment(
        OrderInterface $order,
        ShipmentTrack $shipmentTrack,
        StoreInterface $store
    ) {
        $operation = new ApiOrderOperation();
        $reference = $order->getMarketplaceOrderNumber();
        $channelName = $order->getMarketplaceName();

        $operation->ship(
            $reference,
            $channelName,
            $shipmentTrack->getCarrierTitle(),
            $shipmentTrack->getTrackingNumber(),
            $shipmentTrack->getTrackingUrl()
        );

        $this->executeApiOrderOperation($store, $order, TicketInterface::ACTION_SHIP, $operation);
    }

    /**
     * @param StoreInterface $store
     * @throws \Exception
     */
    public function notifyStoreOrderShipmentUpdates(StoreInterface $store)
    {
        $maximumDelay = $this->orderGeneralConfig->getShipmentSyncingMaximumDelay($store);

        $shippedCollection = $this->orderCollectionFactory->create();
        $shippedCollection->addStoreIdFilter($store->getId());
        $shippedCollection->addIsFulfilledFilter(false);
        $shippedCollection->addNotifiableShipmentFilter();
        $shippedCollection->setCurPage(1);
        $shippedCollection->setPageSize($this->notificationSliceSize);

        $sliceCount = 0;

        do {
            $shippedSalesOrderIds = [];
            $shippedCollection->clear();

            foreach ($shippedCollection as $marketplaceOrder) {
                $shippedSalesOrderIds[] = (int) $marketplaceOrder->getSalesOrderId();
            }

            $salesShipmentTracks = $this->salesShipmentTrackCollector->getOrdersShipmentTracks($shippedSalesOrderIds);

            foreach ($shippedCollection as $marketplaceOrder) {
                $salesOrderId = (int) $marketplaceOrder->getSalesOrderId();

                if (!empty($salesShipmentTracks[$salesOrderId])) {
                    /** @var ShipmentTrack $chosenTrack */
                    $chosenTrack = null;

                    foreach ($salesShipmentTracks[$salesOrderId] as $shipmentTrack) {
                        if (
                            (null === $chosenTrack)
                            || ($shipmentTrack->getRelevance() >= $chosenTrack->getRelevance())
                        ) {
                            $chosenTrack = $shipmentTrack;
                        }
                    }

                    if (
                        ($maximumDelay > 0)
                        && !$chosenTrack->hasTrackingData()
                        && ($chosenTrack->getDelay() <= $maximumDelay)
                    ) {
                        continue;
                    }

                    $this->notifyStoreOrderShipment($marketplaceOrder, $chosenTrack, $store);
                }
            }
        } while (!empty($shippedSalesOrderIds) && (++$sliceCount < $this->maxNotificationIterations));
    }

    /**
     * @param StoreInterface $store
     * @throws \Exception
     */
    public function notifyStoreOrderUpdates(StoreInterface $store)
    {
        $this->refreshPendingTicketStatuses($store);
        $this->notifyStoreOrderImportUpdates($store);
        $this->notifyStoreOrderCancellationUpdates($store);
        $this->notifyStoreOrderShipmentUpdates($store);
    }

    /**
     * @param OrderInterface $order
     * @param string $type
     * @param string $message
     * @param string $details
     * @throws CouldNotSaveException
     */
    public function logOrderMessage(OrderInterface $order, $type, $message, $details = '')
    {
        $log = $this->orderLogFactory->create();
        $log->setOrderId($order->getId());
        $log->setType($type);
        $log->setMessage($message);
        $log->setDetails($details);
        $this->orderLogRepository->save($log);
    }

    /**
     * @param OrderInterface $order
     * @param string $message
     * @param string $details
     * @throws CouldNotSaveException
     */
    public function logOrderDebug(OrderInterface $order, $message, $details = '')
    {
        $this->logOrderMessage($order, LogInterface::TYPE_DEBUG, $message, $details);
    }

    /**
     * @param OrderInterface $order
     * @param string $message
     * @param string $details
     * @throws CouldNotSaveException
     */
    public function logOrderInfo(OrderInterface $order, $message, $details = '')
    {
        $this->logOrderMessage($order, LogInterface::TYPE_INFO, $message, $details);
    }

    /**
     * @param OrderInterface $order
     * @param string $message
     * @param string $details
     * @throws CouldNotSaveException
     */
    public function logOrderError(OrderInterface $order, $message, $details = '')
    {
        $this->logOrderMessage($order, LogInterface::TYPE_ERROR, $message, $details);
    }
}

