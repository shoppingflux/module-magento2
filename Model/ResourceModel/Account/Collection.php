<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Account;

use ShoppingFeed\Manager\Api\Data\AccountInterface;
use ShoppingFeed\Manager\Model\Account;
use ShoppingFeed\Manager\Model\ResourceModel\AbstractCollection;
use ShoppingFeed\Manager\Model\ResourceModel\Account as AccountResource;


/**
 * @method AccountResource getResource()
 */
class Collection extends AbstractCollection
{
    protected $_idFieldName = AccountInterface::ACCOUNT_ID;

    protected function _construct()
    {
        $this->_init(Account::class, AccountResource::class);
    }

    /**
     * @param int|int[] $accountIds
     * @return $this
     */
    public function addIdFilter($accountIds)
    {
        $this->addFieldToFilter(AccountInterface::ACCOUNT_ID, [ 'in' => $this->prepareIdFilterValue($accountIds) ]);
        return $this;
    }
}
