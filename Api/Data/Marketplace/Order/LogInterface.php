<?php

namespace ShoppingFeed\Manager\Api\Data\Marketplace\Order;

/**
 * @api
 */
interface LogInterface
{
    const TYPE_DEBUG = 'debug';
    const TYPE_INFO = 'info';
    const TYPE_ERROR = 'error';

    /**#@+*/
    const LOG_ID = 'log_id';
    const ORDER_ID = 'order_id';
    const TYPE = 'type';
    const MESSAGE = 'message';
    const DETAILS = 'details';
    const CREATED_AT = 'created_at';
    /**#@+*/

    /**
     * @return int
     */
    public function getId();

    /**
     * @return int
     */
    public function getOrderId();

    /**
     * @return string
     */
    public function getType();

    /**
     * @return string
     */
    public function getMessage();

    /**
     * @return string
     */
    public function getDetails();

    /**
     * @return string
     */
    public function getCreatedAt();

    /**
     * @param int $orderId
     * @return LogInterface
     */
    public function setOrderId($orderId);

    /**
     * @param string $type
     * @return LogInterface
     */
    public function setType($type);

    /**
     * @param string $message
     * @return LogInterface
     */
    public function setMessage($message);

    /**
     * @param string $details
     * @return LogInterface
     */
    public function setDetails($details);

    /**
     * @param string $createdAt
     * @return LogInterface
     */
    public function setCreatedAt($createdAt);
}
