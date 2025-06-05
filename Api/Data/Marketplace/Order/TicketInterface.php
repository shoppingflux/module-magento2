<?php

namespace ShoppingFeed\Manager\Api\Data\Marketplace\Order;

/**
 * @api
 */
interface TicketInterface
{
    const ACTION_ACKNOWLEDGE_SUCCESS = 'acknowledge_success';
    const ACTION_ACKNOWLEDGE_FAILURE = 'acknowledge_failure';
    const ACTION_CANCEL = 'cancel';
    const ACTION_SHIP = 'ship';
    const ACTION_DELIVER = 'deliver';
    const ACTION_UPLOAD_INVOICE_PDF = 'upload_invoice_pdf';

    const STATUS_PENDING = 0;
    const STATUS_HANDLED = 1;
    const STATUS_FAILED = 2;

    const API_STATUS_SCHEDULED = 'scheduled';
    const API_STATUS_RUNNING = 'running';
    const API_STATUS_CANCELED = 'canceled';
    const API_STATUS_SUCCEED = 'succeed';
    const API_STATUS_FAILED = 'failed';

    const API_PENDING_STATUSES = [
        self::API_STATUS_SCHEDULED,
        self::API_STATUS_RUNNING,
    ];

    /**#@+*/
    const TICKET_ID = 'log_id';
    const SHOPPING_FEED_BATCH_ID = 'shopping_feed_batch_id';
    const SHOPPING_FEED_TICKET_ID = 'shopping_feed_ticket_id';
    const ORDER_ID = 'order_id';
    const SALES_ENTITY_ID = 'sales_entity_id';
    const ACTION = 'action';
    const STATUS = 'status';
    const CREATED_AT = 'created_at';
    /**#@+*/

    /**
     * @return int
     */
    public function getId();

    /**
     * @return string|null
     */
    public function getShoppingFeedBatchId();

    /**
     * @return string|null
     */
    public function getShoppingFeedTicketId();

    /**
     * @return int
     */
    public function getOrderId();

    /**
     * @return int|null
     */
    public function getSalesEntityId();

    /**
     * @return string
     */
    public function getAction();

    /**
     * @return int
     */
    public function getStatus();

    /**
     * @return string
     */
    public function getCreatedAt();

    /**
     * @param string $batchId
     * @return TicketInterface
     */
    public function setShoppingFeedBatchId($batchId);

    /**
     * @param string $ticketId
     * @return TicketInterface
     */
    public function setShoppingFeedTicketId($ticketId);

    /**
     * @param int $orderId
     * @return TicketInterface
     */
    public function setOrderId($orderId);

    /**
     * @param int|null $entityId
     * @return TicketInterface
     */
    public function setSalesEntityId($entityId);

    /**
     * @param string $action
     * @return TicketInterface
     */
    public function setAction($action);

    /**
     * @param int $status
     * @return TicketInterface
     */
    public function setStatus($status);

    /**
     * @param string $createdAt
     * @return TicketInterface
     */
    public function setCreatedAt($createdAt);
}
