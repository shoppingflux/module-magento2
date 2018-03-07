<?php

namespace ShoppingFeed\Manager\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;


class Account extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('sfm_account', 'account_id');
    }
}
