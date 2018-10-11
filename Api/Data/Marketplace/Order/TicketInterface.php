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

    const STATUS_PENDING = 0;
    const STATUS_HANDLED = 1;
    const STATUS_FAILED = 2;

    /**#@+*/
    const TICKET_ID = 'log_id';
    const SHOPPING_FEED_TICKET_ID = 'shopping_feed_ticket_id';
    const ORDER_ID = 'order_id';
    const ACTION = 'action';
    const STATUS = 'status';
    const CREATED_AT = 'created_at';
    /**#@+*/

    /**
     * @return int
     */
    public function getId();

    /**
     * @return int
     */
    public function getShoppingFeedTicketId();

    /**
     * @return int
     */
    public function getOrderId();

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
     * @param int $ticketId
     * @return TicketInterface
     */
    public function setShoppingFeedTicketId($ticketId);

    /**
     * @param int $orderId
     * @return TicketInterface
     */
    public function setOrderId($orderId);

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
