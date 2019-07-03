<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Account\Store\Create\Form;

use ShoppingFeed\Manager\Controller\Adminhtml\Account\StoreAction;

class Unexisting extends StoreAction
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::account_store_create_unexisting';

    public function execute()
    {
        $pageResult = $this->initPage();
        $pageResult->addBreadcrumb(__('Create an Account'), __('Create an Account'));
        $pageResult->getConfig()->getTitle()->prepend(__('Create an Account'));
        return $pageResult;
    }
}
