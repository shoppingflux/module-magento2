<?php

namespace ShoppingFeed\Manager\Model\Marketplace\Order;

use GuzzleHttp\Exception\BadResponseException;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory as SalesInvoiceCollectionFactory;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\LogInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\LogInterfaceFactory as OrderLogInterfaceFactory;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\TicketInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\TicketInterfaceFactory as OrderTicketInterfaceFactory;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface;
use ShoppingFeed\Manager\Api\Marketplace\Order\LogRepositoryInterface as OrderLogRepositoryInterface;
use ShoppingFeed\Manager\Api\Marketplace\Order\TicketRepositoryInterface as OrderTicketRepositoryInterface;
use ShoppingFeed\Manager\Api\Sales\Invoice\Pdf\ProcessorInterface as PdfProcessorInterface;
use ShoppingFeed\Manager\Api\Sales\Invoice\Pdf\ProcessorPoolInterface as InvoicePdfProcessorPoolInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Collection as OrderCollection;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\CollectionFactory as OrderCollectionFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Ticket\CollectionFactory as OrderTicketCollectionFactory;
use ShoppingFeed\Manager\Model\Sales\Order\ConfigInterface as OrderConfigInterface;
use ShoppingFeed\Manager\Model\Sales\Order\Shipment\Track as ShipmentTrack;
use ShoppingFeed\Manager\Model\Sales\Order\Shipment\Track\Collector as SalesShipmentTrackCollector;
use ShoppingFeed\Manager\Model\Sales\Order\SyncerInterface as SalesOrderSyncerInterface;
use ShoppingFeed\Manager\Model\ShoppingFeed\Api\SessionManager as ApiSessionManager;
use ShoppingFeed\Sdk\Api\Order\Document\Invoice as ApiInvoiceDocument;
use ShoppingFeed\Sdk\Api\Order\Identifier\Id as ApiOrderId;
use ShoppingFeed\Sdk\Api\Order\Operation as ApiOrderOperation;
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
     * @var Filesystem
     */
    private $fileSystem;

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
     * @var SalesInvoiceCollectionFactory
     */
    private $salesInvoiceCollectionFactory;

    /**
     * @var InvoicePdfProcessorPoolInterface
     */
    private $invoicePdfProcessorPool;

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
     * @param SalesInvoiceCollectionFactory|null $salesInvoiceCollectionFactory
     * @param InvoicePdfProcessorPoolInterface|null $invoicePdfProcessorPool
     * @param Filesystem|null $fileSystem
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
        OrderTicketCollectionFactory $orderTicketCollectionFactory = null,
        SalesInvoiceCollectionFactory $salesInvoiceCollectionFactory = null,
        InvoicePdfProcessorPoolInterface $invoicePdfProcessorPool = null,
        Filesystem $fileSystem = null
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

        $objectManager = ObjectManager::getInstance();

        $this->orderTicketCollectionFactory = $orderTicketCollectionFactory
            ?? $objectManager->get(OrderTicketCollectionFactory::class);

        $this->salesInvoiceCollectionFactory = $salesInvoiceCollectionFactory
            ?? $objectManager->get(SalesInvoiceCollectionFactory::class);

        $this->invoicePdfProcessorPool = $invoicePdfProcessorPool
            ?? $objectManager->get(InvoicePdfProcessorPoolInterface::class);

        $this->fileSystem = $fileSystem ?? $objectManager->get(Filesystem::class);
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
                    $ticketStatus = null;

                    try {
                        if ($batchId = $ticket->getShoppingFeedBatchId()) {
                            foreach ($ticketApi->getByBatch($batchId) as $batchTicket) {
                                $apiTicket = $batchTicket;
                                break;
                            }
                        } elseif ($ticketId = $ticket->getShoppingFeedTicketId()) {
                            $apiTicket = $ticketApi->getOne($ticketId);
                        }

                        if ($apiTicket instanceof ApiTicket) {
                            $ticketStatus = $apiTicket->getStatus();
                        }
                    } catch (BadResponseException $e) {
                        if ($e->hasResponse() && ($e->getResponse()->getStatusCode() === 404)) {
                            $ticketStatus = TicketInterface::API_STATUS_FAILED;
                        }
                    }

                    if ($ticketStatus && !in_array($ticketStatus, TicketInterface::API_PENDING_STATUSES, true)) {
                        $ticket->setStatus(
                            ($ticketStatus === TicketInterface::API_STATUS_FAILED)
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
     * @param int|null $salesEntityId
     * @throws CouldNotSaveException
     */
    private function registerOrderApiTicket(OrderInterface $order, ApiTicket $apiTicket, $action, $salesEntityId = null)
    {
        $ticket = $this->orderTicketFactory->create();
        $ticket->setShoppingFeedTicketId(trim((string) $apiTicket->getId()));
        $ticket->setOrderId($order->getId());
        $ticket->setSalesEntityId($salesEntityId);
        $ticket->setAction($action);
        $ticket->setStatus(TicketInterface::STATUS_PENDING);
        $this->orderTicketRepository->save($ticket);
    }

    /**
     * @param OrderInterface $order
     * @param ApiTicket $apiTicket
     * @param string $action
     * @param int|null $salesEntityId
     * @throws CouldNotSaveException
     */
    private function registerOrderApiTicketBatch(OrderInterface $order, string $batchId, $action, $salesEntityId = null)
    {
        $ticket = $this->orderTicketFactory->create();
        $ticket->setShoppingFeedBatchId($batchId);
        $ticket->setOrderId($order->getId());
        $ticket->setSalesEntityId($salesEntityId);
        $ticket->setAction($action);
        $ticket->setStatus(TicketInterface::STATUS_PENDING);
        $this->orderTicketRepository->save($ticket);
    }

    /**
     * @param StoreInterface $store
     * @param OrderInterface $order
     * @param string $action
     * @param ApiOrderOperation $operation
     * @param int|null $salesEntityId
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    private function executeApiOrderOperation(
        StoreInterface $store,
        OrderInterface $order,
        $action,
        ApiOrderOperation $operation,
        $salesEntityId = null
    ) {
        $result = $this->apiSessionManager
            ->getStoreApiResource($store)
            ->getOrderApi()
            ->execute($operation);

        $apiTickets = $result->getTickets();

        foreach ($apiTickets as $apiTicket) {
            $this->registerOrderApiTicket($order, $apiTicket, $action, $salesEntityId);

            return;
        }

        foreach ($result->getBatchIds() as $batchId) {
            $this->registerOrderApiTicketBatch($order, $batchId, $action, $salesEntityId);

            return;
        }
    }

    /**
     * @param OrderInterface $order
     * @return ApiOrderId
     */
    private function getOrderApiId(OrderInterface $order)
    {
        return new ApiOrderId($order->getShoppingFeedOrderId());
    }

    /**
     * @param OrderCollection $collection
     */
    private function loadOrderCollectionNextSliceWithoutDuplicates(OrderCollection $collection)
    {
        if ($collection->isLoaded()) {
            $orderIds = $collection->getLoadedIds();
            $collection->clear();
            $collection->addExcludedIdsFilter($orderIds);
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

        if (TicketInterface::ACTION_ACKNOWLEDGE_SUCCESS === $action) {
            $apiStatus = self::API_ACKNOWLEDGEMENT_STATUS_SUCCESS;
        } else {
            $apiStatus = self::API_ACKNOWLEDGEMENT_STATUS_FAILURE;
        }

        $operation->acknowledge(
            $this->getOrderApiId($order),
            $storeReference,
            $apiStatus
        );

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
            $this->loadOrderCollectionNextSliceWithoutDuplicates($importedCollection);

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

        $operation->cancel(
            new ApiOrderId($order->getShoppingFeedOrderId()),
        );

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
            $this->loadOrderCollectionNextSliceWithoutDuplicates($cancelledCollection);

            $hasNotifiedOrders = $cancelledCollection->count() > 0;

            foreach ($cancelledCollection as $order) {
                $this->notifyStoreOrderCancellation($order, $store);
            }
        } while ($hasNotifiedOrders && (++$sliceCount < $this->maxNotificationIterations));
    }

    /**
     * @param OrderInterface $order
     * @param InvoiceInterface $invoice
     * @param PdfProcessorInterface $pdfProcessor
     * @param StoreInterface $store
     * @throws \Exception
     */
    public function notifyStoreOrderInvoicePdfUpdate(
        OrderInterface $order,
        InvoiceInterface $invoice,
        PdfProcessorInterface $pdfProcessor,
        StoreInterface $store
    ) {
        $pdfContent = $pdfProcessor->getInvoicePdfContent($invoice);

        $operation = new ApiOrderOperation();

        if (strlen($pdfContent) >= 2 * 1024 * 1024) {
            throw new LocalizedException(__('PDF file is too large'));
        }

        $tempDirectory = $this->fileSystem->getDirectoryWrite(DirectoryList::TMP);
        $pdfPath = $tempDirectory->getAbsolutePath(sprintf('sfm-invoice-%d.pdf', $invoice->getId()));
        $tempDirectory->writeFile($pdfPath, $pdfContent);

        $operation->uploadDocument(
            new ApiOrderId($order->getShoppingFeedOrderId()),
            new ApiInvoiceDocument($pdfPath)
        );

        $this->executeApiOrderOperation(
            $store,
            $order,
            TicketInterface::ACTION_UPLOAD_INVOICE_PDF,
            $operation,
            $invoice->getId()
        );

        try {
            $tempDirectory->delete($pdfPath);
        } catch (\Exception $e) {
            // Failure to delete the temporary file is not relevant.
        }
    }

    /**
     * @param StoreInterface $store
     */
    public function notifyStoreOrderInvoicePdfUpdates(StoreInterface $store)
    {
        if (!$this->orderGeneralConfig->isInvoicePdfUploadEnabled($store)) {
            return;
        }

        $pdfProcessor = $this->invoicePdfProcessorPool->getProcessorByCode(
            $this->orderGeneralConfig->getInvoicePdfProcessorCode($store)
        );

        if (!$pdfProcessor || !$pdfProcessor->isAvailable()) {
            return;
        }

        $invoicedCollection = $this->orderCollectionFactory->create();
        $invoicedCollection->addStoreIdFilter($store->getId());
        $invoicedCollection->addUploadableInvoicePdfFilter($this->orderGeneralConfig->getInvoicePdfUploadDelay($store));
        $invoicedCollection->setCurPage(1);
        $invoicedCollection->setPageSize($this->notificationSliceSize);

        $sliceCount = 0;

        do {
            $this->loadOrderCollectionNextSliceWithoutDuplicates($invoicedCollection);

            $invoiceOrderIds = [];

            foreach ($invoicedCollection as $order) {
                $orderId = (int) $order->getId();

                $invoiceIds = array_filter(
                    array_map(
                        'intval',
                        explode(',', (string) $order->getData('invoice_ids'))
                    )
                );

                foreach ($invoiceIds as $invoiceId) {
                    $invoiceOrderIds[$invoiceId] = $orderId;
                }
            }

            if (empty($invoiceOrderIds)) {
                $hasUploadedPdf = false;
            } else {
                $invoiceCollection = $this->salesInvoiceCollectionFactory->create();
                $invoiceCollection->addFieldToFilter('entity_id', [ 'in' => array_keys($invoiceOrderIds) ]);

                foreach ($invoiceCollection as $invoice) {
                    $invoiceId = (int) $invoice->getId();

                    /** @var OrderInterface $order */
                    if (
                        !isset($invoiceOrderIds[$invoiceId])
                        || (!$order = $invoicedCollection->getItemById($invoiceOrderIds[$invoiceId]))
                        || (!$marketplace = $order->getMarketplaceName())
                        || !$this->orderGeneralConfig->shouldUploadInvoicePdfForMarketplace($store, $marketplace)
                    ) {
                        continue;
                    }

                    try {
                        $this->notifyStoreOrderInvoicePdfUpdate($order, $invoice, $pdfProcessor, $store);
                    } catch (\Exception $e) {
                        $this->logOrderError(
                            $order,
                            __('Failed to upload invoice PDF:') . "\n" . $e->getMessage(),
                            (string) $e
                        );
                    }
                }

                $hasUploadedPdf = $invoiceCollection->count() > 0;
            }
        } while ($hasUploadedPdf && (++$sliceCount < $this->maxNotificationIterations));
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

        $operation->ship(
            $this->getOrderApiId($order),
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
            $this->loadOrderCollectionNextSliceWithoutDuplicates($shippedCollection);

            $shippedSalesOrderIds = [];

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
     * @param OrderInterface $order
     * @param StoreInterface $store
     * @throws \Exception
     */
    public function notifyStoreOrderDelivery(OrderInterface $order, StoreInterface $store)
    {
        $operation = new ApiOrderOperation();

        $operation->deliver($this->getOrderApiId($order));

        $this->executeApiOrderOperation($store, $order, TicketInterface::ACTION_DELIVER, $operation);
    }

    /**
     * @param StoreInterface $store
     */
    public function notifyStoreOrderDeliveryUpdates(StoreInterface $store)
    {
        if (!$this->orderGeneralConfig->shouldSyncDeliveredOrders($store)) {
            return;
        }

        $deliveredCollection = $this->orderCollectionFactory->create();
        $deliveredCollection->addStoreIdFilter($store->getId());

        $deliveredCollection->addNotifiableDeliveryFilter(
            $this->orderGeneralConfig->getOrderDeliveredStatuses($store),
            $this->orderGeneralConfig->getDeliverySyncingMaximumDelay($store)
        );

        $deliveredCollection->setCurPage(1);
        $deliveredCollection->setPageSize($this->notificationSliceSize);

        $sliceCount = 0;

        do {
            $this->loadOrderCollectionNextSliceWithoutDuplicates($deliveredCollection);

            $hasNotifiedOrders = $deliveredCollection->count() > 0;

            foreach ($deliveredCollection as $order) {
                $this->notifyStoreOrderDelivery($order, $store);
            }
        } while ($hasNotifiedOrders && (++$sliceCount < $this->maxNotificationIterations));
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
        $this->notifyStoreOrderDeliveryUpdates($store);
        $this->notifyStoreOrderInvoicePdfUpdates($store);
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
