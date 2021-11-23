<?php

namespace ShoppingFeed\Manager\Model\Marketplace;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Api\Marketplace\OrderRepositoryInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order as OrderResource;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\OrderFactory as OrderResourceFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\CollectionFactory as OrderCollectionFactory;

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
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @param OrderResourceFactory $orderResourceFactory
     * @param OrderFactory $orderFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     */
    public function __construct(
        OrderResourceFactory $orderResourceFactory,
        OrderFactory $orderFactory,
        OrderCollectionFactory $orderCollectionFactory
    ) {
        $this->orderResource = $orderResourceFactory->create();
        $this->orderFactory = $orderFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
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

    public function getByMarketplaceIdAndNumber($marketplaceId, $marketplaceNumber)
    {
        $orderCollection = $this->orderCollectionFactory->create();

        $orderCollection->addMarketplaceIdFilter($marketplaceId);
        $orderCollection->addMarketplaceNumberFilter($marketplaceNumber);

        $orderIds = $orderCollection->getAllIds();

        if (empty($orderIds)) {
            throw new NoSuchEntityException(
                __(
                    'Marketplace order with marketplace ID "%1" and number "%2" does not exist.',
                    $marketplaceId,
                    $marketplaceNumber
                )
            );
        }

        return $this->getById($orderIds[0]);
    }

    public function getByStoreAndMarketplaceIdAndNumber($storeId, $marketplaceId, $marketplaceNumber)
    {
        $orderCollection = $this->orderCollectionFactory->create();

        $orderCollection->addStoreIdFilter($storeId);
        $orderCollection->addMarketplaceIdFilter($marketplaceId);
        $orderCollection->addMarketplaceNumberFilter($marketplaceNumber);

        $orderIds = $orderCollection->getAllIds();

        if (empty($orderIds)) {
            throw new NoSuchEntityException(
                __(
                    'Marketplace order with marketplace ID "%1" and number "%2" does not exist.',
                    $marketplaceId,
                    $marketplaceNumber,
                    $storeId
                )
            );
        }

        return $this->getById($orderIds[0]);
    }

    public function getBySalesOrderId($orderId)
    {
        $order = $this->orderFactory->create();
        $this->orderResource->load($order, $orderId, OrderInterface::SALES_ORDER_ID);

        if (!$order->getId()) {
            throw new NoSuchEntityException(
                __('Marketplace order with sales order ID "%1" does not exist.', $orderId)
            );
        }

        return $order;
    }
}
