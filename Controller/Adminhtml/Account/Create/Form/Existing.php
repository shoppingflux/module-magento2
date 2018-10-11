<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Account\Create\Form;

use ShoppingFeed\Manager\Controller\Adminhtml\AccountAction;

class Existing extends AccountAction
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::account_create_existing';

    public function execute()
    {
        $pageResult = $this->initPage();
        $pageResult->addBreadcrumb(__('New Account - Existing'), __('New Account - Existing'));
        $pageResult->getConfig()->getTitle()->prepend(__('New Account - Existing'));
        return $pageResult;
    }
}
