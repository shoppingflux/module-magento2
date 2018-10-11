<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Shipping\Method\Rule;

use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Controller\Adminhtml\Shipping\Method\RuleAction;
use ShoppingFeed\Manager\Model\Shipping\Method\Rule\RegistryConstants;

class Edit extends RuleAction
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::shipping_method_rule_edit';

    public function execute()
    {
        try {
            $rule = $this->getRule();
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This shipping method rule does no longer exist.'));
            $redirectResult = $this->resultRedirectFactory->create();
            return $redirectResult->setPath('*/*/');
        }

        $this->coreRegistry->register(RegistryConstants::CURRENT_SHIPPING_METHOD_RULE, $rule);
        return $this->initPage()->addBreadcrumb(__('Edit Shipping Method Rule'), __('Edit Shipping Method Rule'));
    }
}
