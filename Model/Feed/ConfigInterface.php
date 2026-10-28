<?php

namespace ShoppingFeed\Manager\Model\Feed;

use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\DataObject;
use ShoppingFeed\Manager\Model\Account\Store\ConfigInterface as BaseConfig;

interface ConfigInterface extends BaseConfig
{
    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldUseGzipCompression(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldForceSecureUrls(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @param DataObject $dataA
     * @param DataObject $dataB
     * @return bool
     */
    public function isSectionRefreshNeededForStoreDataChange(
        StoreInterface $store,
        DataObject $dataA,
        DataObject $dataB
    );
}
