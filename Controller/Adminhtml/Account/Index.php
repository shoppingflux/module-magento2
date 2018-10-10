<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Account;

use ShoppingFeed\Manager\Controller\Adminhtml\AccountAction;

class Index extends AccountAction
{
    public function execute()
    {
        return $this->initPage();
    }
}
