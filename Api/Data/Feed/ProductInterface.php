<?php

namespace ShoppingFeed\Manager\Api\Data\Feed;

/**
 * @api
 */
interface ProductInterface
{
    const STATE_EXPORTED = 1;
    const STATE_RETAINED = 2;
    const STATE_NOT_EXPORTED = 3;
    const STATE_NEVER_EXPORTED = 4;

    const ALL_EXPORT_STATES = [
        self::STATE_EXPORTED,
        self::STATE_RETAINED,
        self::STATE_NOT_EXPORTED,
        self::STATE_NEVER_EXPORTED,
    ];

    const EXPORTED_STATES = [
        self::STATE_EXPORTED,
        self::STATE_RETAINED,
    ];

    const REFRESH_STATE_UP_TO_DATE = 1;
    const REFRESH_STATE_ADVISED = 2;
    const REFRESH_STATE_REQUIRED = 3;

    const ALL_REFRESH_STATES = [
        self::REFRESH_STATE_UP_TO_DATE,
        self::REFRESH_STATE_ADVISED,
        self::REFRESH_STATE_REQUIRED,
    ];

    const EXCLUSION_REASON_UNHANDLED_PRODUCT_TYPE = 1;
    const EXCLUSION_REASON_NOT_IN_WEBSITE = 2;
    const EXCLUSION_REASON_NOT_SALABLE = 3;
    const EXCLUSION_REASON_OUT_OF_STOCK = 4;
    const EXCLUSION_REASON_FILTERED_VISIBILITY = 5;
    const EXCLUSION_REASON_UNSELECTED_PRODUCT = 6;
    const EXCLUSION_REASON_FILTERED_PRODUCT_TYPE = 7;
    const EXCLUSION_REASON_DISABLED = 8;

    /**#@+*/
    const PRODUCT_ID = 'product_id';
    const STORE_ID = 'store_id';
    const IS_SELECTED = 'is_selected';
    const SELECTED_CATEGORY_ID = 'selected_category_id';
    const EXPORT_STATE = 'export_state';
    const CHILD_EXPORT_STATE = 'child_export_state';
    const EXCLUSION_REASON = 'exclusion_reason';
    const EXPORT_RETENTION_STARTED_AT = 'export_retention_started_at';
    const EXPORT_RETENTION_STARTED_AT_TIMESTAMP = 'export_retention_started_at_timestamp';
    const EXPORT_STATE_REFRESHED_AT = 'export_state_refreshed_at';
    const EXPORT_STATE_REFRESHED_AT_TIMESTAMP = 'export_state_refreshed_at_timestamp';
    const EXPORT_STATE_REFRESH_STATE = 'export_state_refresh_state';
    const EXPORT_STATE_REFRESH_STATE_UPDATED_AT = 'export_state_refresh_state_updated_at';
    const EXPORT_STATE_REFRESH_STATE_UPDATED_AT_TIMESTAMP = 'export_state_refresh_state_updated_at_timestamp';
    /**#@+*/

    /**
     * @return int
     */
    public function getId();

    /**
     * @return int
     */
    public function getStoreId();

    /**
     * @return bool
     */
    public function isSelected();

    /**
     * @return int|null
     */
    public function getSelectedCategoryId();

    /**
     * @return int
     */
    public function getExportState();

    /**
     * @return int
     */
    public function getChildExportState();

    /**
     * @return int|null
     */
    public function getExclusionReason();

    /**
     * @return string|null
     */
    public function getExportRetentionStartedAt();

    /**
     * @return int|null
     */
    public function getExportRetentionStartedAtTimestamp();

    /**
     * @return string|null
     */
    public function getExportStateRefreshedAt();

    /**
     * @return int|null
     */
    public function getExportStateRefreshedAtTimestamp();

    /**
     * @return int
     */
    public function getExportStateRefreshState();

    /**
     * @return string
     */
    public function getExportStateRefreshStateUpdatedAt();

    /**
     * @return int
     */
    public function getExportStateRefreshStateUpdatedAtTimestamp();

    /**
     * @param int $id
     * @return ProductInterface
     */
    public function setId($id);

    /**
     * @param int $storeId
     * @return ProductInterface
     */
    public function setStoreId($storeId);

    /**
     * @param bool $isSelected
     * @return ProductInterface
     */
    public function setIsSelected($isSelected);

    /**
     * @param int $selectedCategoryId
     * @return ProductInterface
     */
    public function setSelectedCategoryId($selectedCategoryId);

    /**
     * @param int $exportState
     * @return ProductInterface
     */
    public function setExportState($exportState);

    /**
     * @param int $childExportState
     * @return ProductInterface
     */
    public function setChildExportState($childExportState);

    /**
     * @param int|null $exclusionReason
     * @return ProductInterface
     */
    public function setExclusionReason($exclusionReason);

    /**
     * @param string $retentionStartedAt
     * @return ProductInterface
     */
    public function setExportRetentionStartedAt($retentionStartedAt);

    /**
     * @param string $exportStateRefreshedAt
     * @return ProductInterface
     */
    public function setExportStateRefreshedAt($exportStateRefreshedAt);

    /**
     * @param int $refreshState
     * @return ProductInterface
     */
    public function setExportStateRefreshState($refreshState);

    /**
     * @param string $refreshStateUpdatedAt
     * @return ProductInterface
     */
    public function setExportStateRefreshStateUpdatedAt($refreshStateUpdatedAt);
}
