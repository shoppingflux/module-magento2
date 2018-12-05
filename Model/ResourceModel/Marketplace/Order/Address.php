<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order;

use ShoppingFeed\Manager\Api\Data\Marketplace\Order\AddressInterface;
use ShoppingFeed\Manager\Model\ResourceModel\AbstractDb;

class Address extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('sfm_marketplace_order_address', AddressInterface::ADDRESS_ID);
    }
}
