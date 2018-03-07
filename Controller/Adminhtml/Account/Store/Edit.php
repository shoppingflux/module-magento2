<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Account\Store;

use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Controller\Adminhtml\Account\Store;
use ShoppingFeed\Manager\Model\Account\Store\RegistryConstants;


class Edit extends Store
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::account_store_edit';

    public function execute()
    {
        try {
            $store = $this->getStore();
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This account store does no longer exist.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/');
        }

        $this->coreRegistry->register(RegistryConstants::CURRENT_ACCOUNT_STORE, $store);
        $resultPage = $this->initPage()->addBreadcrumb(__('Edit Account Store'), __('Edit Account Store'));

        return $resultPage;
    }
}
