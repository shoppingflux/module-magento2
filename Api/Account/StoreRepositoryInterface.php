<?php

namespace ShoppingFeed\Manager\Api\Account;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;


/**
 * @api
 */
interface StoreRepositoryInterface
{
    /**
     * @param StoreInterface $store
     * @return StoreInterface
     * @throws CouldNotSaveException
     */
    public function save(StoreInterface $store);

    /**
     * @param int $storeId
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    public function getById($storeId);

    /**
     * @param int $shoppingFeedStoreId
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    public function getByShoppingFeedStoreId($shoppingFeedStoreId);

    /**
     * @param StoreInterface $store
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(StoreInterface $store);

    /**
     * @param int $storeId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById($storeId);
}
