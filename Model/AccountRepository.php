<?php

namespace ShoppingFeed\Manager\Model;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Api\AccountRepositoryInterface;
use ShoppingFeed\Manager\Api\Data\AccountInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Account as AccountResource;
use ShoppingFeed\Manager\Model\ResourceModel\Account\CollectionFactory as AccountCollectionFactory;
use ShoppingFeed\Manager\Model\ResourceModel\AccountFactory as AccountResourceFactory;

class AccountRepository implements AccountRepositoryInterface
{
    /**
     * @var AccountResource
     */
    private $accountResource;

    /**
     * @var AccountFactory
     */
    private $accountFactory;

    /**
     * @var
     */
    private $accountCollectionFactory;

    /**
     * @param AccountResourceFactory $accountResourceFactory
     * @param AccountFactory $accountFactory
     * @param AccountCollectionFactory|null $accountCollectionFactory
     */
    public function __construct(
        AccountResourceFactory $accountResourceFactory,
        AccountFactory $accountFactory,
        ?AccountCollectionFactory $accountCollectionFactory = null
    ) {
        $this->accountResource = $accountResourceFactory->create();
        $this->accountFactory = $accountFactory;
        $this->accountCollectionFactory = $accountCollectionFactory
            ?? ObjectManager::getInstance()->get(AccountCollectionFactory::class);
    }

    public function save(AccountInterface $account)
    {
        try {
            $this->accountResource->save($account);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $account;
    }

    public function getById($accountId)
    {
        $account = $this->accountFactory->create();
        $this->accountResource->load($account, $accountId);

        if (!$account->getId()) {
            throw new NoSuchEntityException(__('Account for ID "%1" does not exist.', $accountId));
        }

        return $account;
    }

    public function getByApiToken($apiToken)
    {
        $accounts = $this->accountCollectionFactory->create();

        foreach ($accounts as $account) {
            if ($account->getApiToken() === $apiToken) {
                return $account;
            }
        }

        throw new NoSuchEntityException(__('Account for API token "%1" does not exist.', $apiToken));
    }

    public function delete(AccountInterface $account)
    {
        try {
            $this->accountResource->delete($account);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    public function deleteById($accountId)
    {
        return $this->delete($this->getById($accountId));
    }
}
