<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Account\Store;

use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Controller\Adminhtml\Account\StoreAction;

class Delete extends StoreAction
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::account_store_delete';

    public function execute()
    {
        try {
            $store = $this->getStore();
            $this->storeRepository->delete($store);
            $this->messageManager->addSuccessMessage(__('The account store has been successfully deleted.'));
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This account store does no longer exist.'));
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('An error occurred while deleting the account store.'));
        }

        $redirectResult = $this->resultRedirectFactory->create();
        return $redirectResult->setPath('*/*/');
    }
}
