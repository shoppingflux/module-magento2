<?php

namespace ShoppingFeed\Manager\Model\Feed;

use ShoppingFeed\Manager\Api\Data\Feed\ProductInterface;
use ShoppingFeed\Manager\DataObject;


class Product extends DataObject implements ProductInterface
{
    protected $timestampFields = [
        self::EXPORT_RETENTION_STARTED_AT => self::EXPORT_RETENTION_STARTED_AT_TIMESTAMP,
        self::EXPORT_STATE_REFRESHED_AT => self::EXPORT_STATE_REFRESHED_AT_TIMESTAMP,
        self::EXPORT_STATE_REFRESH_STATE_UPDATED_AT => self::EXPORT_STATE_REFRESH_STATE_UPDATED_AT_TIMESTAMP,
    ];

    public function getId()
    {
        return (int) $this->getDataByKey(self::PRODUCT_ID);
    }

    public function getStoreId()
    {
        return (int) $this->getDataByKey(self::STORE_ID);
    }

    public function isSelected()
    {
        return (bool) $this->getDataByKey(self::IS_SELECTED);
    }

    public function getSelectedCategoryId()
    {
        $categoryId = $this->getDataByKey(self::SELECTED_CATEGORY_ID);
        return empty($categoryId) ? null : (int) $categoryId;
    }

    public function getExportState()
    {
        return (int) $this->getDataByKey(self::EXPORT_STATE);
    }

    public function getChildExportState()
    {
        return (int) $this->getDataByKey(self::CHILD_EXPORT_STATE);
    }

    public function getExportRetentionStartedAt()
    {
        return $this->getDataByKey(self::EXPORT_RETENTION_STARTED_AT);
    }

    public function getExportRetentionStartedAtTimestamp()
    {
        return $this->getDataByKey(self::EXPORT_RETENTION_STARTED_AT_TIMESTAMP);
    }

    public function getExportStateRefreshedAt()
    {
        return $this->getDataByKey(self::EXPORT_STATE_REFRESHED_AT);
    }

    public function getExportStateRefreshedAtTimestamp()
    {
        return $this->getDataByKey(self::EXPORT_STATE_REFRESHED_AT_TIMESTAMP);
    }

    public function getExportStateRefreshState()
    {
        return (int) $this->getDataByKey(self::EXPORT_STATE_REFRESH_STATE);
    }

    public function getExportStateRefreshStateUpdatedAt()
    {
        return $this->getDataByKey(self::EXPORT_STATE_REFRESH_STATE_UPDATED_AT);
    }

    public function getExportStateRefreshStateUpdatedAtTimestamp()
    {
        return $this->getDataByKey(self::EXPORT_STATE_REFRESH_STATE_UPDATED_AT_TIMESTAMP);
    }

    public function setId($id)
    {
        return $this->setData(self::PRODUCT_ID, (int) $id);
    }

    public function setStoreId($storeId)
    {
        return $this->setData(self::STORE_ID, (int) $storeId);
    }

    public function setIsSelected($isSelected)
    {
        return $this->setData(self::IS_SELECTED, (bool) $isSelected);
    }

    public function setSelectedCategoryId($selectedCategoryId)
    {
        return $this->setData(
            self::SELECTED_CATEGORY_ID,
            empty($selectedCategoryId) ? null : (int) $selectedCategoryId
        );
    }

    public function setExportState($exportState)
    {
        return $this->setData(self::EXPORT_STATE, (int) $exportState);
    }

    public function setChildExportState($childExportState)
    {
        return $this->setData(self::CHILD_EXPORT_STATE, (int) $childExportState);
    }

    public function setExportRetentionStartedAt($retentionStartedAt)
    {
        return $this->setData(self::EXPORT_RETENTION_STARTED_AT, $retentionStartedAt);
    }

    public function setExportStateRefreshedAt($exportStateRefreshedAt)
    {
        return $this->setData(self::EXPORT_STATE_REFRESHED_AT, $exportStateRefreshedAt);
    }

    public function setExportStateRefreshState($refreshState)
    {
        return $this->setData(self::EXPORT_STATE_REFRESH_STATE, (int) $refreshState);
    }

    public function setExportStateRefreshStateUpdatedAt($refreshStateUpdatedAt)
    {
        return $this->setData(self::EXPORT_STATE_REFRESH_STATE_UPDATED_AT, $refreshStateUpdatedAt);
    }
}
