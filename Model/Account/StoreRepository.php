<?php

namespace ShoppingFeed\Manager\Model\Account;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Api\Account\StoreRepositoryInterface;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store as StoreResource;
use ShoppingFeed\Manager\Model\ResourceModel\Account\StoreFactory as StoreResourceFactory;


class StoreRepository implements StoreRepositoryInterface
{
    /**
     * @var StoreResource
     */
    protected $storeResource;

    /**
     * @var StoreFactory
     */
    protected $storeFactory;

    /**
     * @param StoreResourceFactory $resourceFactory
     * @param StoreFactory $storeFactory
     */
    public function __construct(StoreResourceFactory $resourceFactory, StoreFactory $storeFactory)
    {
        $this->storeResource = $resourceFactory->create();
        $this->storeFactory = $storeFactory;
    }

    /**
     * @param StoreInterface $store
     * @return StoreInterface
     * @throws CouldNotSaveException
     */
    public function save(StoreInterface $store)
    {
        try {
            $this->storeResource->save($store);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $store;
    }

    /**
     * @param string $storeId
     * @return Store
     * @throws NoSuchEntityException
     */
    public function getById($storeId)
    {
        $account = $this->storeFactory->create();
        $this->storeResource->load($account, $storeId);

        if (!$account->getId()) {
            throw new NoSuchEntityException(__('Shopping Feed account store with ID "%1" does not exist.', $storeId));
        }

        return $account;
    }

    /**
     * @param StoreInterface $store
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(StoreInterface $store)
    {
        try {
            $this->storeResource->delete($store);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    /**
     * @param int $storeId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById($storeId)
    {
        return $this->delete($this->getById($storeId));
    }
}
