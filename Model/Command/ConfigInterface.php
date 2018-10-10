<?php

namespace ShoppingFeed\Manager\Model\Command;

use Magento\Framework\DataObject;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Config\Basic\ConfigInterface as BaseConfigInterface;

interface ConfigInterface extends BaseConfigInterface
{
    /**
     * @return bool
     */
    public function isAppliableByStore();

    /**
     * @param DataObject $configData
     * @return int[]
     */
    public function getStoreIds(DataObject $configData);

    /**
     * @param DataObject $configData
     * @return StoreInterface[]
     */
    public function getStores(DataObject $configData);
}
