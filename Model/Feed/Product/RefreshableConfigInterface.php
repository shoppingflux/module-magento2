<?php

namespace ShoppingFeed\Manager\Model\Feed\Product;

use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\DataObject;

interface RefreshableConfigInterface
{
    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldForceProductLoadForRefresh(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return int|false
     */
    public function getAutomaticRefreshState(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * return int
     */
    public function getAutomaticRefreshDelay(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function isAdvisedRefreshRequirementEnabled(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return int
     */
    public function getAdvisedRefreshRequirementDelay(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @param DataObject $dataA
     * @param DataObject $dataB
     * @return bool
     */
    public function isRefreshNeededForStoreDataChange(StoreInterface $store, DataObject $dataA, DataObject $dataB);
}

// @todo on save/import some adapters can partially/fully detect what has changed
// @todo let the user choose what to do every time, and when changes have been detected
// @todo (most likely, force advised or required refresh, but this should depend on sections types too, etc.)
