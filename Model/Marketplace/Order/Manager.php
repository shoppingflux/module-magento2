<?php

namespace ShoppingFeed\Manager\Model\Marketplace\Order;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\LogInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\TicketInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as OrderInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\LogInterfaceFactory as OrderLogInterfaceFactory;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\TicketInterfaceFactory as OrderTicketInterfaceFactory;
use ShoppingFeed\Manager\Api\Marketplace\Order\LogRepositoryInterface as OrderLogRepositoryInterface;
use ShoppingFeed\Manager\Api\Marketplace\Order\TicketRepositoryInterface as OrderTicketRepositoryInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\CollectionFactory as OrderCollectionFactory;
use ShoppingFeed\Manager\Model\ShoppingFeed\Api\SessionManager as ApiSessionManager;
use ShoppingFeed\Sdk\Api\Order\OrderOperation as ApiOrderOperation;
use ShoppingFeed\Sdk\Api\Order\OrderResource as ApiOrder;
use ShoppingFeed\Sdk\Api\Task\TicketResource as ApiTicket;


class Manager
{
    const API_FILTER_STATUS = 'status';
    const API_FILTER_ACKNOWLEDGEMENT = 'acknowledgment';

    const API_ACKNOWLEDGEMENT_STATUS_SUCCESS = 'success';
    const API_ACKNOWLEDGEMENT_STATUS_FAILURE = 'error';

    const API_ACKNOWLEDGED = 'acknowledged';
    const API_UNACKNOWLEDGED = 'unacknowledged';

    /**
     * @var ApiSessionManager
     */
    private $apiSessionManager;

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
     * @param ApiSessionManager $apiSessionManager
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param OrderLogInterfaceFactory $orderLogFactory
     * @param OrderLogRepositoryInterface $orderLogRepository
     * @param OrderTicketInterfaceFactory $orderTicketFactory
     * @param OrderTicketRepositoryInterface $orderTicketRepository
     */
    public function __construct(
        ApiSessionManager $apiSessionManager,
        OrderCollectionFactory $orderCollectionFactory,
        OrderLogInterfaceFactory $orderLogFactory,
        OrderLogRepositoryInterface $orderLogRepository,
        OrderTicketInterfaceFactory $orderTicketFactory,
        OrderTicketRepositoryInterface $orderTicketRepository
    ) {
        $this->apiSessionManager = $apiSessionManager;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderLogFactory = $orderLogFactory;
        $this->orderLogRepository = $orderLogRepository;
        $this->orderTicketFactory = $orderTicketFactory;
        $this->orderTicketRepository = $orderTicketRepository;
    }

    /**
     * @param StoreInterface $store
     * @return ApiOrder[]
     * @throws LocalizedException
     */
    public function getStoreImportableApiOrders(StoreInterface $store)
    {
        $apiStore = $this->apiSessionManager->getStoreApiResource($store);
        return $apiStore->getOrderApi()->getAll([ self::API_FILTER_ACKNOWLEDGEMENT => self::API_UNACKNOWLEDGED ]);
    }

    /**
     * @param StoreInterface $store
     * @param int|null $maximumCount
     * @return OrderInterface[]
     */
    public function getStoreImportableOrders(StoreInterface $store, $maximumCount = null)
    {
        $orderCollection = $this->orderCollectionFactory->create();
        $orderCollection->addNonImportedFilter();
        $orderCollection->addImportableFilter();
        $orderCollection->addStoreIdFilter($store->getId());

        if (null !== $maximumCount) {
            $orderCollection->setCurPage(1);
            $orderCollection->setPageSize($maximumCount);
        }

        $orderCollection->load();
        return $orderCollection->getItems();
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
        $ticket->setShoppingFeedTicketId($apiTicket->getId());
        $ticket->setOrderId($order->getId());
        $ticket->setAction($action);
        $ticket->setStatus(TicketInterface::STATUS_HANDLED);
        $this->orderTicketRepository->save($ticket);
    }

    /**
     * @param OrderInterface $order
     * @param string $storeReference
     * @param string $action
     * @param StoreInterface $store
     * @throws \Exception
     */
    private function notifyOrderImportResult(OrderInterface $order, $storeReference, $action, StoreInterface $store)
    {
        $apiStore = $this->apiSessionManager->getStoreApiResource($store);

        $operation = new ApiOrderOperation();
        $reference = $order->getMarketplaceOrderNumber();
        $channelName = $order->getMarketplaceName();

        if (TicketInterface::ACTION_ACKNOWLEDGE_SUCCESS === $action) {
            $apiStatus = self::API_ACKNOWLEDGEMENT_STATUS_SUCCESS;
        } else {
            $apiStatus = self::API_ACKNOWLEDGEMENT_STATUS_FAILURE;
        }

        $operation->acknowledge($reference, $channelName, $apiStatus, $storeReference);

        $apiTickets = $apiStore->getOrderApi()
            ->execute($operation)
            ->getAcknowledge($reference);

        if (isset($apiTickets[0]) && $apiTickets[0]->getId()) {
            try {
                $this->registerOrderApiTicket($order, $apiTickets[0], $action);
            } catch (\Exception $e) {
                $operation = new ApiOrderOperation();
                $operation->unacknowledge($reference, $channelName, $apiStatus, $storeReference);
                $apiStore->getOrderApi()->execute($operation);
                throw $e;
            }
        }
    }

    /**
     * @param OrderInterface $order
     * @param string $salesIncrementId
     * @param StoreInterface $store
     * @throws \Exception
     */
    public function notifyOrderImportSuccess(OrderInterface $order, $salesIncrementId, StoreInterface $store)
    {
        $this->notifyOrderImportResult($order, $salesIncrementId, TicketInterface::ACTION_ACKNOWLEDGE_SUCCESS, $store);
    }

    /**
     * @param OrderInterface $order
     * @param StoreInterface $store
     * @throws \Exception
     */
    public function notifyOrderImportFailure(OrderInterface $order, StoreInterface $store)
    {
        $this->notifyOrderImportResult(
            $order,
            ((string) $order->getId()),
            TicketInterface::ACTION_ACKNOWLEDGE_FAILURE,
            $store
        );
    }

    /**
     * @param OrderInterface $order
     * @param StoreInterface $store
     * @throws \Exception
     */
    public function notifyOrderCancellation(OrderInterface $order, StoreInterface $store)
    {
        $apiStore = $this->apiSessionManager->getStoreApiResource($store);

        $operation = new ApiOrderOperation();
        $reference = $order->getMarketplaceOrderNumber();
        $channelName = $order->getMarketplaceName();
        $operation->cancel($reference, $channelName);

        $apiTickets = $apiStore->getOrderApi()
            ->execute($operation)
            ->getCanceled($reference);

        if (isset($apiTickets[0]) && $apiTickets[0]->getId()) {
            $this->registerOrderApiTicket($order, $apiTickets[0], TicketInterface::ACTION_CANCEL);
        }
    }

    /**
     * @param OrderInterface $order
     * @param string|null $carrierTitle
     * @param string|null $trackingNumber
     * @param string|null $trackingUrl
     * @param StoreInterface $store
     * @throws \Exception
     */
    public function notifyOrderShipment(
        OrderInterface $order,
        $carrierTitle,
        $trackingNumber,
        $trackingUrl,
        StoreInterface $store
    ) {
        $apiStore = $this->apiSessionManager->getStoreApiResource($store);

        $operation = new ApiOrderOperation();
        $reference = $order->getMarketplaceOrderNumber();
        $channelName = $order->getMarketplaceName();
        $operation->ship($reference, $channelName, $carrierTitle, $trackingNumber, $trackingUrl);

        $apiTickets = $apiStore->getOrderApi()
            ->execute($operation)
            ->getShipped($reference);

        if (isset($apiTickets[0]) && $apiTickets[0]->getId()) {
            $this->registerOrderApiTicket($order, $apiTickets[0], TicketInterface::ACTION_SHIP);
        }
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
