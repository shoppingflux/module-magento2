<?php

namespace ShoppingFeed\Manager\Model\Feed;

use ShoppingFeed\Manager\Model\AbstractModel;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product as ProductResource;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product\Collection as ProductCollection;


/**
 * @method ProductResource getResource()
 * @method ProductCollection getCollection()
 * @method int|null getExportRetentionStartedAtTimestamp()
 * @method int|null getExportStateRefreshedAtTimestamp()
 * @method int getExportStateRefreshStateUpdatedAtTimestamp()
 */
class Product extends AbstractModel
{
    protected $_eventPrefix = 'shoppingfeed_manager_feed_product';
    protected $_eventObject = 'feed_product';

    protected $timestampFields = [
        'export_retention_started_at' => 'export_retention_started_at_timestamp',
        'export_state_refreshed_at' => 'export_state_refreshed_at_timestamp',
        'export_state_refresh_state_updated_at' => 'export_state_refresh_state_updated_at_timestamp',
    ];

    const STATE_EXPORTED = 1;
    const STATE_RETAINED = 2;
    const STATE_NOT_EXPORTED = 3;
    const STATE_NEVER_EXPORTED = 4;

    protected function _construct()
    {
        $this->_init(ProductResource::class);
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return (int) $this->getDataByKey('store_id');
    }

    /**
     * @return int
     */
    public function getExportState()
    {
        return (int) $this->getDataByKey('export_state');
    }

    /**
     * @return int
     */
    public function getChildExportState()
    {
        return (int) $this->getDataByKey('child_export_state');
    }

    /**
     * @return int
     */
    public function getExportStateRefreshState()
    {
        return (int) $this->getDataByKey('export_state_refresh_state');
    }

    /**
     * @return bool
     */
    public function isSelected()
    {
        return (bool) $this->getDataByKey('is_selected');
    }

    /**
     * @return bool
     */
    public function isExported()
    {
        return in_array((int) $this->getExportState(), [ self::STATE_EXPORTED, self::STATE_RETAINED ], true);
    }
}
