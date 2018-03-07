<?php

namespace ShoppingFeed\Manager\Model\Account\Store;

use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Account\Store\Config\AbstractField;


interface ConfigInterface
{
    /**
     * @return string
     */
    public function getScope();

    /**
     * @return string[]
     */
    public function getScopeSubPath();

    /**
     * @return AbstractField[]
     */
    public function getFields();

    /**
     * @param string $name
     * @return AbstractField|null
     */
    public function getField($name);

    /**
     * @param StoreInterface $store
     * @param string $name
     * @return mixed|null
     */
    public function getStoreFieldValue(StoreInterface $store, $name);

    /**
     * @return string
     */
    public function getFieldsetLabel();
}
