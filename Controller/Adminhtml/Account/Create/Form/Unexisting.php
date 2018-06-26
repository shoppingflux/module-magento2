<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Account\Create\Form;

use ShoppingFeed\Manager\Controller\Adminhtml\AccountAction;


class Unexisting extends AccountAction
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::account_create_unexisting';

    public function execute()
    {
        $pageResult = $this->initPage();
        $pageResult->addBreadcrumb(__('New Account - Registration'), __('New Account - Registration'));
        $pageResult->getConfig()->getTitle()->prepend(__('New Account - Registration'));
        return $pageResult;
    }
}
