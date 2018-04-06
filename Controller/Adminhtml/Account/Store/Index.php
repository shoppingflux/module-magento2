<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Account\Store;

use ShoppingFeed\Manager\Controller\Adminhtml\Account\StoreAction;


class Index extends StoreAction
{
    public function execute()
    {
        return $this->initPage();
    }
}
