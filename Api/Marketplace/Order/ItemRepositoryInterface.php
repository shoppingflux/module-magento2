<?php

namespace ShoppingFeed\Manager\Api\Marketplace\Order;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\ItemInterface;

/**
 * @api
 */
interface ItemRepositoryInterface
{
    /**
     * @param ItemInterface $item
     * @return ItemInterface
     * @throws CouldNotSaveException
     */
    public function save(ItemInterface $item);

    /**
     * @param int $itemId
     * @return ItemInterface
     * @throws NoSuchEntityException
     */
    public function getById($itemId);
}
