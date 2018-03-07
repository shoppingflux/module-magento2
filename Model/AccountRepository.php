<?php

namespace ShoppingFeed\Manager\Model;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Api\AccountRepositoryInterface;
use ShoppingFeed\Manager\Api\Data\AccountInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Account as AccountResource;
use ShoppingFeed\Manager\Model\ResourceModel\AccountFactory as AccountResourceFactory;


class AccountRepository implements AccountRepositoryInterface
{
    /**
     * @var AccountResource
     */
    protected $accountResource;

    /**
     * @var AccountFactory
     */
    protected $accountFactory;

    /**
     * @param AccountResourceFactory $accountResourceFactory
     * @param AccountFactory $accountFactory
     */
    public function __construct(AccountResourceFactory $accountResourceFactory, AccountFactory $accountFactory)
    {
        $this->accountResource = $accountResourceFactory->create();
        $this->accountFactory = $accountFactory;
    }

    /**
     * @param AccountInterface $account
     * @return AccountInterface
     * @throws CouldNotSaveException
     */
    public function save(AccountInterface $account)
    {
        try {
            $this->accountResource->save($account);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $account;
    }

    /**
     * @param int $accountId
     * @return AccountInterface
     * @throws NoSuchEntityException
     */
    public function getById($accountId)
    {
        $account = $this->accountFactory->create();
        $this->accountResource->load($account, $accountId);

        if (!$account->getId()) {
            throw new NoSuchEntityException(__('Shopping Feed account with ID "%1" does not exist.', $accountId));
        }

        return $account;
    }

    /**
     * @param string $apiToken
     * @return AccountInterface
     * @throws NoSuchEntityException
     */
    public function getByApiToken($apiToken)
    {
        $account = $this->accountFactory->create();
        $this->accountResource->load($account, $apiToken, AccountInterface::API_TOKEN);

        if (!$account->getId()) {
            throw new NoSuchEntityException(__('Shopping Feed account with API token "%1" does not exist.', $apiToken));
        }

        return $account;
    }

    /**
     * @param AccountInterface $account
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(AccountInterface $account)
    {
        try {
            $this->accountResource->delete($account);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    /**
     * @param int $accountId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById($accountId)
    {
        return $this->delete($this->getById($accountId));
    }
}
