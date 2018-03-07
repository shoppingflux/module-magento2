<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Account\Store;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Controller\Adminhtml\Account\Store;


class Save extends Store
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::account_store_edit';

    public function execute()
    {
        $redirectResult = $this->resultRedirectFactory->create();

        try {
            $store = $this->getStore();
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This account store does no longer exist.'));
            return $redirectResult->setPath('*/*/');
        }

        $store->importConfigurationData($this->getRequest()->getPostValue());
        $isSaveSuccessful = false;

        try {
            $this->storeRepository->save($store);
            $isSaveSuccessful = true;
            $this->messageManager->addSuccessMessage(__('The account store has been successfully saved.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addExceptionMessage($e->getPrevious() ?: $e);
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while creating the account.'));
        }

        if (!$isSaveSuccessful || $this->getRequest()->getParam('back')) {
            return $redirectResult->setPath('*/*/edit', [ 'store_id' => $store->getId() ]);
        }

        return $redirectResult->setPath('*/*/');
    }
}
