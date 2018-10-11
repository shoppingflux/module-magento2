<?php

namespace ShoppingFeed\Manager\Api\Data\Account;

use Magento\Catalog\Model\ResourceModel\Product\Collection as CatalogProductCollection;
use Magento\Store\Model\Store as BaseStore;
use ShoppingFeed\Manager\Api\Data\AccountInterface;
use ShoppingFeed\Manager\DataObject;

interface StoreInterface
{
    /**#@+*/
    const STORE_ID = 'store_id';
    const ACCOUNT_ID = 'account_id';
    const BASE_STORE_ID = 'base_store_id';
    const SHOPPING_FEED_STORE_ID = 'shopping_feed_store_id';
    const SHOPPING_FEED_NAME = 'shopping_feed_name';
    const CONFIGURATION = 'configuration';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    /**#@-*/

    /**
     * @return int|null
     */
    public function getId();

    /**
     * @return int
     */
    public function getAccountId();

    /**
     * @return AccountInterface
     */
    public function getAccount();

    /**
     * @return int
     */
    public function getBaseStoreId();

    /**
     * @return BaseStore
     */
    public function getBaseStore();

    /**
     * @return int
     */
    public function getShoppingFeedStoreId();

    /**
     * @return string
     */
    public function getShoppingFeedName();

    /**
     * @return DataObject
     */
    public function getConfiguration();

    /**
     * @return string
     */
    public function getCreatedAt();

    /**
     * @return string
     */
    public function getUpdatedAt();

    /**
     * @param string $path
     * @return mixed
     */
    public function getScopeConfigValue($path);

    /**
     * @return int[]
     */
    public function getSelectedFeedProductIds();

    /**
     * @return CatalogProductCollection
     */
    public function getCatalogProductCollection();

    /**
     * @param int $id
     * @return StoreInterface
     */
    public function setId($id);

    /**
     * @param int $accountId
     * @return StoreInterface
     */
    public function setAccountId($accountId);

    /**
     * @param int $baseStoreId
     * @return StoreInterface
     */
    public function setBaseStoreId($baseStoreId);

    /**
     * @param int $shoppingFeedStoreId
     * @return StoreInterface
     */
    public function setShoppingFeedStoreId($shoppingFeedStoreId);

    /**
     * @param string $shoppingFeedName
     * @return StoreInterface
     */
    public function setShoppingFeedName($shoppingFeedName);

    /**
     * @param DataObject $configuration
     * @return StoreInterface
     */
    public function setConfiguration(DataObject $configuration);

    /**
     * @param string $createdAt
     * @return StoreInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * @param string $updatedAt
     * @return StoreInterface
     */
    public function setUpdatedAt($updatedAt);

    /**
     * @param array $data
     * @return StoreInterface
     */
    public function importConfigurationData(array $data);
}
