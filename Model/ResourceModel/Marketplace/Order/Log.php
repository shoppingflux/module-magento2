<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\LogInterface;

class Log extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('sfm_marketplace_order_log', LogInterface::LOG_ID);
    }
}
