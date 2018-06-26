<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Account;

use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Controller\Adminhtml\AccountAction;


class Delete extends AccountAction
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::account_delete';

    public function execute()
    {
        $redirectResult = $this->resultRedirectFactory->create();
        $redirectResult->setPath('*/*/');

        try {
            $account = $this->getAccount();
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This account does no longer exist.'));
            return $redirectResult;
        }

        try {
            $this->accountRepository->delete($account);
            $this->messageManager->addSuccessMessage(__('The account has been successfully deleted.'));
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('An error occurred while deleting the account.'));
        }

        return $redirectResult;
    }
}
