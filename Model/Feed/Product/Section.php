<?php

namespace ShoppingFeed\Manager\Model\Feed\Product;

use ShoppingFeed\Manager\Api\Data\Feed\Product\SectionInterface;
use ShoppingFeed\Manager\DataObject;

class Section extends DataObject implements SectionInterface
{
    protected $timestampFields = [
        self::REFRESHED_AT => self::REFRESHED_AT_TIMESTAMP,
        self::REFRESH_STATE_UPDATED_AT => self::REFRESH_STATE_UPDATED_AT_TIMESTAMP,
    ];

    /**
     * @return int
     */
    public function getTypeId()
    {
        return (int) $this->getData(self::TYPE_ID);
    }

    /**
     * @return int
     */
    public function getProductId()
    {
        return (int) $this->getData(self::PRODUCT_ID);
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return (int) $this->getData(self::STORE_ID);
    }

    /**
     * @return array
     */
    public function getFeedData()
    {
        return (array) $this->getData(self::FEED_DATA);
    }

    /**
     * @return string|null
     */
    public function getRefreshedAt()
    {
        return $this->getData(self::REFRESHED_AT);
    }

    /**
     * @return int|null
     */
    public function getRefreshedAtTimestamp()
    {
        return $this->getData(self::REFRESHED_AT_TIMESTAMP);
    }

    /**
     * @return int
     */
    public function getRefreshState()
    {
        return $this->getData(self::REFRESH_STATE);
    }

    /**
     * @return string
     */
    public function getRefreshStateUpdatedAt()
    {
        return (int) $this->getData(self::REFRESH_STATE_UPDATED_AT);
    }

    /**
     * @return int
     */
    public function getRefreshStateUpdatedAtTimestamp()
    {
        return $this->getData(self::REFRESH_STATE_UPDATED_AT_TIMESTAMP);
    }

    /**
     * @param int $typeId
     * @return SectionInterface
     */
    public function setTypeId($typeId)
    {
        return $this->setData(self::TYPE_ID, (int) $typeId);
    }

    /**
     * @param int $productId
     * @return SectionInterface
     */
    public function setProductId($productId)
    {
        return $this->setData(self::PRODUCT_ID, (int) $productId);
    }

    /**
     * @param int $storeId
     * @return SectionInterface
     */
    public function setStoreId($storeId)
    {
        return $this->setData(self::STORE_ID, (int) $storeId);
    }

    /**
     * @param array $data
     * @return SectionInterface
     */
    public function setFeedData(array $data)
    {
        return $this->setData(self::FEED_DATA, $data);
    }

    /**
     * @param string|null $refreshedAt
     * @return SectionInterface
     */
    public function setRefreshedAt($refreshedAt)
    {
        return $this->setData(self::REFRESHED_AT, $refreshedAt);
    }

    /**
     * @param int $refreshState
     * @return SectionInterface
     */
    public function setRefreshState($refreshState)
    {
        return $this->setData(self::REFRESH_STATE, (int) $refreshState);
    }

    /**
     * @param string $refreshStateUpdatedAt
     * @return SectionInterface
     */
    public function setRefreshStateUpdatedAt($refreshStateUpdatedAt)
    {
        return $this->setData(self::REFRESH_STATE_UPDATED_AT, $refreshStateUpdatedAt);
    }
}
