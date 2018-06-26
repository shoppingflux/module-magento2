<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Marketplace;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;


class Order extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('sfm_marketplace_order', 'order_id');
    }
}
