<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Account\Create\Form;

use ShoppingFeed\Manager\Controller\Adminhtml\AccountAction;


class Unexisting extends AccountAction
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::account_create_unexisting';
    
    public function execute()
    {
        $resultPage = $this->initPage();
        $resultPage->addBreadcrumb(__('New Account - Registration'), __('New Account - Registration'));
        $resultPage->getConfig()->getTitle()->prepend(__('New Account - Registration'));
        return $resultPage;
    }
}
