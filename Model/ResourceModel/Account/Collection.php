<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Account;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use ShoppingFeed\Manager\Model\Account;
use ShoppingFeed\Manager\Model\ResourceModel\Account as AccountResource;


/**
 * @method AccountResource getResource()
 */
class Collection extends AbstractCollection
{
    protected $_idFieldName = 'account_id';

    protected function _construct()
    {
        $this->_init(Account::class, AccountResource::class);
    }
}
