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

    public function save(StoreInterface $store)
    {
        try {
            $this->storeResource->save($store);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $store;
    }

    public function getById($storeId)
    {
        $store = $this->storeFactory->create();
        $this->storeResource->load($store, $storeId);

        if (!$store->getId()) {
            throw new NoSuchEntityException(__('Account store for ID "%1" does not exist.', $storeId));
        }

        return $store;
    }

    public function getByShoppingFeedStoreId($shoppingFeedStoreId)
    {
        $store = $this->storeFactory->create();
        $this->storeResource->load($store, $shoppingFeedStoreId, StoreInterface::SHOPPING_FEED_STORE_ID);

        if (!$store->getId()) {
            throw new NoSuchEntityException(
                __('Account store for Shopping Feed store ID "%1" does not exist.', $shoppingFeedStoreId)
            );
        }

        return $store;
    }

    public function delete(StoreInterface $store)
    {
        try {
            $this->storeResource->delete($store);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    public function deleteById($storeId)
    {
        return $this->delete($this->getById($storeId));
    }
}
