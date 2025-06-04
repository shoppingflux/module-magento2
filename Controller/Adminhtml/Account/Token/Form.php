<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Account\Token;

use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Controller\Adminhtml\AccountAction;
use ShoppingFeed\Manager\Model\Account\RegistryConstants;

class Form extends AccountAction
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::account_token_update';

    public function execute()
    {
        try {
            $account = $this->getAccount();
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This account does no longer exist.'));
            $redirectResult = $this->resultRedirectFactory->create();
            return $redirectResult->setPath('*/account_store/');
        }

        $this->coreRegistry->register(RegistryConstants::CURRENT_ACCOUNT, $account);
        $pageResult = $this->initPage();
        $pageResult->addBreadcrumb(__('Edit an Account'), __('Edit an Account'));
        $pageResult->getConfig()->getTitle()->prepend(__('Edit an Account'));

        return $pageResult;
    }
}