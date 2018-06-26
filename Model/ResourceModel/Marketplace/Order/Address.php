<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;


class Address extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('sfm_marketplace_order_address', 'address_id');
    }
}
