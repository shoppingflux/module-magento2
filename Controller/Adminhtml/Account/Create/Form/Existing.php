<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Account\Create\Form;

use ShoppingFeed\Manager\Controller\Adminhtml\Account;


class Existing extends Account
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::account_create_existing';
    
    public function execute()
    {
        $resultPage = $this->initPage();
        $resultPage->addBreadcrumb(__('New Account - Existing'), __('New Account - Existing'));
        $resultPage->getConfig()->getTitle()->prepend(__('New Account - Existing'));
        return $resultPage;
    }
}
