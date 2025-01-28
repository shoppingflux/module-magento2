<?php

namespace ShoppingFeed\Manager\Api\Data\Marketplace\Order;

use ShoppingFeed\Manager\DataObject;

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
    const TAX_AMOUNT = 'tax_amount';
    const ADDITIONAL_FIELDS = 'additional_fields';
    /**#@+*/

    const ADDITIONAL_FIELD_ARTICLE_ID = 'article_id';
    const ADDITIONAL_FIELD_ORDER_ITEM_ID = 'order_item_id';

    const ORDER_ITEM_OPTION_CODE_ADDITIONAL_FIELDS = 'sfm_additional_fields';

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
     * @return float|null
     */
    public function getTaxAmount();

    /**
     * @return DataObject
     */
    public function getAdditionalFields();

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

    /**
     * @param float|null $taxAmount
     * @return ItemInterface
     */
    public function setTaxAmount($taxAmount);

    /**
     * @param DataObject $additionalFields
     * @return ItemInterface
     */
    public function setAdditionalFields(DataObject $additionalFields);
}
