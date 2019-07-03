<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Account\Store;

use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Controller\Adminhtml\Account\StoreAction;
use ShoppingFeed\Manager\Model\Account\Store\RegistryConstants;

class Edit extends StoreAction
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::account_store_edit';

    public function execute()
    {
        try {
            $store = $this->getStore();
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This account does no longer exist.'));
            $redirectResult = $this->resultRedirectFactory->create();
            return $redirectResult->setPath('*/*/');
        }

        $this->coreRegistry->register(RegistryConstants::CURRENT_ACCOUNT_STORE, $store);
        $pageResult = $this->initPage();
        $pageResult->addBreadcrumb(__('Edit an Account'), __('Edit an Account'));
        $pageResult->getConfig()->getTitle()->prepend(__('Edit an Account'));

        return $pageResult;
    }
}
