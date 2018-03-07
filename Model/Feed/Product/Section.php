<?php

namespace ShoppingFeed\Manager\Model\Feed\Product;

use ShoppingFeed\Manager\Model\AbstractModel;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product\Section as SectionResource;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product\Section\Collection as SectionCollection;


/**
 * @method SectionResource getResource()
 * @method SectionCollection getCollection()
 * @method int getTypeId()
 * @method int getProductId()
 * @method int getStoreId()
 * @method int|null getRefreshedAtTimestamp()
 * @method int getRefreshState()
 * @method int getRefreshStateUpdatedAtTimestamp()
 */
class Section extends AbstractModel
{
    protected $_eventPrefix = 'shoppingfeed_manager_feed_product_section';
    protected $_eventObject = 'feed_product_section';

    protected $timestampFields = [
        'refreshed_at' => 'refreshed_at_timestamp',
        'refresh_state_updated_at' => 'refresh_state_updated_at_timestamp',
    ];

    protected function _construct()
    {
        $this->_init(SectionResource::class);
    }
}
