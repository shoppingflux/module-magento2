<?php

namespace ShoppingFeed\Manager\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use ShoppingFeed\Manager\Api\Data\AccountInterface;


class Account extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('sfm_account', AccountInterface::ACCOUNT_ID);
    }
}
