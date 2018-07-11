<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Marketplace\Order;

use ShoppingFeed\Manager\Controller\Adminhtml\Marketplace\OrderAction;


class Index extends OrderAction
{
    public function execute()
    {
        return $this->initPage();
    }
}
