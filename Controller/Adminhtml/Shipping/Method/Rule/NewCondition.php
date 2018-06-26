<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Shipping\Method\Rule;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory as RawResultFactory;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory as PageResultFactory;
use Magento\Rule\Model\Condition\AbstractCondition;
use ShoppingFeed\Manager\Api\Shipping\Method\RuleRepositoryInterface;
use ShoppingFeed\Manager\Block\Adminhtml\Shipping\Method\Rule\Edit\ConditionsForm as RuleConditionsForm;
use ShoppingFeed\Manager\Controller\Adminhtml\Shipping\Method\RuleAction;
use ShoppingFeed\Manager\Model\Shipping\Method\RuleFactory;
use ShoppingFeed\Manager\Ui\DataProvider\Shipping\Method\Rule\Form\DataProvider as RuleFormDataProvider;


class NewCondition extends RuleAction
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::shipping_method_rule_edit';

    public function execute()
    {
        $rawResult = $this->rawResultFactory->create();
        $id = trim($this->getRequest()->getParam('id'));
        $typeValues = array_filter(explode('|', str_replace('-', '/', $this->getRequest()->getParam('type'))));

        if (empty($id) || empty($typeValues)) {
            $rawResult->setContents('');
            return $rawResult;
        }

        $conditionModel = $this->_objectManager->create($typeValues[0]);

        $conditionModel->setId($id);
        $conditionModel->setType($typeValues[0]);
        $conditionModel->setRule($this->ruleFactory->create());
        $conditionModel->setPrefix('conditions');

        if (!empty($typeValues[1])) {
            $conditionModel->setAttribute($typeValues[1]);
        }

        if ($conditionModel instanceof AbstractCondition) {
            $conditionModel->setFormName(RuleFormDataProvider::FORM_NAMESPACE);
            $conditionModel->setJsFormObject(RuleConditionsForm::FIELDSET_ID);
            $conditionHtml = $conditionModel->asHtmlRecursive();
        } else {
            $conditionHtml = '';
        }

        $rawResult->setContents($conditionHtml);
        return $rawResult;
    }
}
