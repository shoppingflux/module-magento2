<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Marketplace\Order\Fetch;

use ShoppingFeed\Manager\Controller\Adminhtml\Marketplace\OrderAction;

class Form extends OrderAction
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::marketplace_order_fetch';

    public function execute()
    {
        $pageResult = $this->initPage();
        $pageResult->addBreadcrumb(__('Fetch an Order'), __('Fetch an Order'));
        $pageResult->getConfig()->getTitle()->prepend(__('Fetch an Order'));
        return $pageResult;
    }
}
