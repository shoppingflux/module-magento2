<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order;

use ShoppingFeed\Manager\Api\Data\Marketplace\Order\ItemInterface;
use ShoppingFeed\Manager\Model\ResourceModel\AbstractDb;

class Item extends AbstractDb
{
    const DATA_OBJECT_FIELD_NAMES = [ ItemInterface::ADDITIONAL_FIELDS ];

    protected function _construct()
    {
        $this->_init('sfm_marketplace_order_item', ItemInterface::ITEM_ID);
    }
}
