<?php

namespace ShoppingFeed\Manager\Api\Data\Feed\Product;


/**
 * @api
 */
interface SectionInterface
{
    /**#@+*/
    const TYPE_ID = 'type_id';
    const PRODUCT_ID = 'product_id';
    const STORE_ID = 'store_id';
    const FEED_DATA = 'data';
    const REFRESHED_AT = 'refreshed_at';
    const REFRESHED_AT_TIMESTAMP = 'refreshed_at_timestamp';
    const REFRESH_STATE = 'refresh_state';
    const REFRESH_STATE_UPDATED_AT = 'refresh_state_updated_at';
    const REFRESH_STATE_UPDATED_AT_TIMESTAMP = 'refresh_state_updated_at_timestamp';
    /**#@+*/

    /**
     * @return int
     */
    public function getTypeId();

    /**
     * @return int
     */
    public function getProductId();

    /**
     * @return int
     */
    public function getStoreId();

    /**
     * @return array
     */
    public function getFeedData();

    /**
     * @return string|null
     */
    public function getRefreshedAt();

    /**
     * @return int|null
     */
    public function getRefreshedAtTimestamp();

    /**
     * @return int
     */
    public function getRefreshState();

    /**
     * @return string
     */
    public function getRefreshStateUpdatedAt();

    /**
     * @return int
     */
    public function getRefreshStateUpdatedAtTimestamp();

    /**
     * @param int $typeId
     * @return SectionInterface
     */
    public function setTypeId($typeId);

    /**
     * @param int $productId
     * @return SectionInterface
     */
    public function setProductId($productId);

    /**
     * @param int $storeId
     * @return SectionInterface
     */
    public function setStoreId($storeId);

    /**
     * @param array $data
     * @return SectionInterface
     */
    public function setFeedData(array $data);

    /**
     * @param string|null $refreshedAt
     * @return SectionInterface
     */
    public function setRefreshedAt($refreshedAt);

    /**
     * @param int $refreshState
     * @return SectionInterface
     */
    public function setRefreshState($refreshState);

    /**
     * @param string $refreshStateUpdatedAt
     * @return SectionInterface
     */
    public function setRefreshStateUpdatedAt($refreshStateUpdatedAt);
}
