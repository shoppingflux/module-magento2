<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Account;

use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Controller\Adminhtml\AccountAction;

class Delete extends AccountAction
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::account_delete';

    public function execute()
    {
        try {
            $account = $this->getAccount();
            $this->accountRepository->delete($account);
            $this->messageManager->addSuccessMessage(__('The account has been successfully deleted.'));
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This account does no longer exist.'));
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('An error occurred while deleting the account.'));
        }

        $redirectResult = $this->resultRedirectFactory->create();
        return $redirectResult->setPath('*/*/');
    }
}
