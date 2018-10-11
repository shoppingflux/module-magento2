<?php

namespace ShoppingFeed\Manager\Api\Marketplace;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface;

/**
 * @api
 */
interface OrderRepositoryInterface
{
    /**
     * @param OrderInterface $order
     * @return OrderInterface
     * @throws CouldNotSaveException
     */
    public function save(OrderInterface $order);

    /**
     * @param int $orderId
     * @return OrderInterface
     * @throws NoSuchEntityException
     */
    public function getById($orderId);

    /**
     * @param int $orderId
     * @return OrderInterface
     * @throws NoSuchEntityException
     */
    public function getByShoppingFeedId($orderId);
}
