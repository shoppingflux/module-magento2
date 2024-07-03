<?php

namespace ShoppingFeed\Manager\Model\Command\Feed\Traits;

use Magento\Framework\DataObject;
use ShoppingFeed\Manager\Api\Account\StoreRepositoryInterface;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Command\ConfigInterface;

trait WithFeedRefreshTrait
{
    /**
     * @var StoreRepositoryInterface
     */
    protected $storeRepository;

    /**
     * @return ConfigInterface
     */
    abstract public function getConfig();

    /**
     * @param DataObject $configData
     * @param callable $callback
     * @return void
     */
    public function withPrioritizedStores(DataObject $configData, callable $callback)
    {
        $stores = $this->getConfig()->getStores($configData);

        usort(
            $stores,
            function (StoreInterface $storeA, StoreInterface $storeB) {
                $lastRefreshAtA = $storeA->getLastCronFeedRefreshAt();
                $lastRefreshAtB = $storeB->getLastCronFeedRefreshAt();

                if (null === $lastRefreshAtA) {
                    return -1;
                } elseif (null === $lastRefreshAtB) {
                    return 1;
                }

                return $lastRefreshAtA <=> $lastRefreshAtB;
            }
        );

        foreach ($stores as $store) {
            $store->setLastCronFeedRefreshAt(gmdate('Y-m-d H:i:s'));
            $this->storeRepository->save($store);
            $callback($store);
        }
    }
}