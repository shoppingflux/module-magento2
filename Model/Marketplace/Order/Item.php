<?php

namespace ShoppingFeed\Manager\Model\Marketplace\Order;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\ItemInterface;
use ShoppingFeed\Manager\DataObject;
use ShoppingFeed\Manager\DataObjectFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Item as ItemResource;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Item\Collection as ItemCollection;

/**
 * @method ItemResource getResource()
 * @method ItemCollection getCollection()
 */
class Item extends AbstractModel implements ItemInterface
{
    protected $_eventPrefix = 'shoppingfeed_manager_marketplace_order_item';
    protected $_eventObject = 'sales_order_item';

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param DataObjectFactory $dataObjectFactory
     * @param ItemResource|null $resource
     * @param ItemCollection|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        DataObjectFactory $dataObjectFactory,
        ?ItemResource $resource = null,
        ?ItemCollection $resourceCollection = null,
        array $data = []
    ) {
        $this->dataObjectFactory = $dataObjectFactory;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init(ItemResource::class);
    }

    public function getOrderId()
    {
        return (int) $this->getDataByKey(self::ORDER_ID);
    }

    public function getShoppingFeedItemId()
    {
        $itemId = (int) $this->getDataByKey(self::SHOPPING_FEED_ITEM_ID);
        return ($itemId > 0) ? $itemId : null;
    }

    public function getReference()
    {
        return trim((string) $this->getDataByKey(self::REFERENCE));
    }

    public function getQuantity()
    {
        return (float) $this->getDataByKey(self::QUANTITY);
    }

    public function getPrice()
    {
        return (float) $this->getDataByKey(self::PRICE);
    }

    public function getTaxAmount()
    {
        $taxAmount = $this->getDataByKey(self::TAX_AMOUNT);
        return (null !== $taxAmount) ? (float) $taxAmount : null;
    }

    public function getAdditionalFields()
    {
        $data = $this->getData(self::ADDITIONAL_FIELDS);

        if (!$data instanceof DataObject) {
            $data = is_string($data) ? json_decode($data, true) : [];
            $data = $this->dataObjectFactory->create([ 'data' => is_array($data) ? $data : [] ]);
            $this->setAdditionalFields($data);
        }

        return $data;
    }

    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, (int) $orderId);
    }

    public function setShoppingFeedItemId($shoppingFeedItemId)
    {
        $itemId = (int) $shoppingFeedItemId;
        return $this->setData(self::SHOPPING_FEED_ITEM_ID, ($itemId > 0) ? $itemId : null);
    }

    public function setReference($reference)
    {
        return $this->setData(self::REFERENCE, trim((string) $reference));
    }

    public function setQuantity($quantity)
    {
        return $this->setData(self::QUANTITY, (float) $quantity);
    }

    public function setPrice($price)
    {
        return $this->setData(self::PRICE, (float) $price);
    }

    public function setTaxAmount($taxAmount)
    {
        return $this->setData(self::TAX_AMOUNT, (null !== $taxAmount) ? (float) $taxAmount : null);
    }

    public function setAdditionalFields(DataObject $additionalFields)
    {
        return $this->setData(self::ADDITIONAL_FIELDS, $additionalFields);
    }
}
