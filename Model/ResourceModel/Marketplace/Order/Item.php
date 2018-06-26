<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;


class Item extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('sfm_marketplace_order_item', 'item_id');
    }
}
