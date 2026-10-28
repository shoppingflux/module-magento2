<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section;

use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Account\Store\ConfigInterface as BaseConfig;
use ShoppingFeed\Manager\Model\Feed\Product\RefreshableConfigInterface;

interface ConfigInterface extends BaseConfig, RefreshableConfigInterface
{
    /**
     * @param AbstractType $type
     * @return $this
     */
    public function setType(AbstractType $type);

    /**
     * @param StoreInterface $store
     * @return bool Whether the section data should be refreshed when the general feed configuration changes.
     */
    public function isRefreshNeededForGeneralConfigChange(StoreInterface $store);
}
