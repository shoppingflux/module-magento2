<?php

namespace ShoppingFeed\Manager\Api;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Api\Data\AccountInterface;


/**
 * @api
 */
interface AccountRepositoryInterface
{
    /**
     * @param AccountInterface $account
     * @return AccountInterface
     * @throws CouldNotSaveException
     */
    public function save(AccountInterface $account);

    /**
     * @param int $accountId
     * @return AccountInterface
     * @throws NoSuchEntityException
     */
    public function getById($accountId);

    /**
     * @param string $apiToken
     * @return AccountInterface
     * @throws NoSuchEntityException
     */
    public function getByApiToken($apiToken);

    /**
     * @param AccountInterface $account
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(AccountInterface $account);

    /**
     * @param int $accountId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById($accountId);
}
