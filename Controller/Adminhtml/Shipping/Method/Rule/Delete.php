<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Shipping\Method\Rule;

use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Controller\Adminhtml\Shipping\Method\RuleAction;


class Delete extends RuleAction
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::shipping_method_rule_delete';

    public function execute()
    {
        try {
            $rule = $this->getRule();
            $this->ruleRepository->delete($rule);
            $this->messageManager->addSuccessMessage(__('The shipping method rule has been successfully deleted.'));
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This shipping method rule does no longer exist.'));
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('An error occurred while deleting the shipping method rule.')
            );
        }

        $redirectResult = $this->resultRedirectFactory->create();
        return $redirectResult->setPath('*/*/');
    }
}
