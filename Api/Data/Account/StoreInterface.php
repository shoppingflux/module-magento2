<?php

namespace ShoppingFeed\Manager\Api\Data\Account;

use Magento\Store\Model\Store as BaseStore;
use Magento\Store\Model\Website as BaseWebsite;
use ShoppingFeed\Manager\Api\Data\AccountInterface;
use ShoppingFeed\Manager\DataObject;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Catalog\Product\Collection as CatalogProductCollection;

interface StoreInterface
{
    /**#@+*/
    const STORE_ID = 'store_id';
    const ACCOUNT_ID = 'account_id';
    const BASE_STORE_ID = 'base_store_id';
    const SHOPPING_FEED_STORE_ID = 'shopping_feed_store_id';
    const SHOPPING_FEED_NAME = 'shopping_feed_name';
    const CONFIGURATION = 'configuration';
    const FEED_FILE_NAME_BASE = 'feed_file_name_base';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const LAST_CRON_FEED_REFRESH_AT = 'last_cron_feed_refresh_at';
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
     * @return int
     */
    public function getBaseWebsiteId();

    /**
     * @return BaseStore
     */
    public function getBaseStore();

    /**
     * @return BaseWebsite
     */
    public function getBaseWebsite();

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
    public function getFeedFileNameBase();

    /**
     * @return string
     */
    public function getCreatedAt();

    /**
     * @return string
     */
    public function getUpdatedAt();

    /**
     * @return string|null
     */
    public function getLastCronFeedRefreshAt();

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
     * @param string $feedFileNameBase
     * @return StoreInterface
     */
    public function setFeedFileNameBase($feedFileNameBase);

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
     * @param string $lastCronFeedRefreshAt
     * @return StoreInterface
     */
    public function setLastCronFeedRefreshAt($lastCronFeedRefreshAt);

    /**
     * @param array $data
     * @return StoreInterface
     * @deprecated 1.0.0
     */
    public function importConfigurationData(array $data);
}
