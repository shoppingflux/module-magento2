<?php

namespace ShoppingFeed\Manager\Api\Data\Marketplace\Order;


/**
 * @api
 */
interface ItemInterface
{
    /**#@+*/
    const ITEM_ID = 'item_id';
    const ORDER_ID = 'order_id';
    const REFERENCE = 'reference';
    const QUANTITY = 'quantity';
    const PRICE = 'price';
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
     * @return int
     */
    public function getReference();

    /**
     * @return float
     */
    public function getQuantity();

    /**
     * @return float
     */
    public function getPrice();

    /**
     * @param int $orderId
     * @return ItemInterface
     */
    public function setOrderId($orderId);

    /**
     * @param string $reference
     * @return ItemInterface
     */
    public function setReference($reference);

    /**
     * @param float $quantity
     * @return ItemInterface
     */
    public function setQuantity($quantity);

    /**
     * @param float $price
     * @return ItemInterface
     */
    public function setPrice($price);
}
