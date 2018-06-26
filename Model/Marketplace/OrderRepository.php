<?php

namespace ShoppingFeed\Manager\Model\Marketplace;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Api\Marketplace\OrderRepositoryInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order as OrderResource;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\OrderFactory as OrderResourceFactory;


class OrderRepository implements OrderRepositoryInterface
{
    /**
     * @var OrderResource
     */
    protected $orderResource;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @param OrderResourceFactory $orderResourceFactory
     * @param OrderFactory $orderFactory
     */
    public function __construct(OrderResourceFactory $orderResourceFactory, OrderFactory $orderFactory)
    {
        $this->orderResource = $orderResourceFactory->create();
        $this->orderFactory = $orderFactory;
    }

    public function save(OrderInterface $order)
    {
        try {
            $this->orderResource->save($order);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $order;
    }

    public function getById($orderId)
    {
        $order = $this->orderFactory->create();
        $this->orderResource->load($order, $orderId);

        if (!$order->getId()) {
            throw new NoSuchEntityException(__('Marketplace order with ID "%1" does not exist.', $orderId));
        }

        return $order;
    }

    public function getByShoppingFeedId($orderId)
    {
        $order = $this->orderFactory->create();
        $this->orderResource->load($order, $orderId, OrderInterface::SHOPPING_FEED_ORDER_ID);

        if (!$order->getId()) {
            throw new NoSuchEntityException(
                __('Marketplace order with Shopping Feed ID "%1" does not exist.', $orderId)
            );
        }

        return $order;
    }
}
