<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Shipping\Method\Rule\Edit;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic as GenericForm;
use Magento\Backend\Block\Widget\Form\Renderer\Fieldset as FieldsetRenderer;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Rule\Block\Conditions as ConditionsRenderer;
use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Rule\Model\Condition\Combine as CombinedCondition;
use ShoppingFeed\Manager\Model\Shipping\Method\Rule as MethodRule;
use ShoppingFeed\Manager\Model\Shipping\Method\Rule\RegistryConstants;
use ShoppingFeed\Manager\Ui\DataProvider\Shipping\Method\Rule\Form\DataProvider as RuleFormDataProvider;

class ConditionsForm extends GenericForm
{
    const HTML_ID_PREFIX = 'rule_';
    const FIELDSET_ID = RuleFormDataProvider::FORM_NAMESPACE . '_' . self::HTML_ID_PREFIX . '_conditions_fieldset';

    /**
     * @var FieldsetRenderer
     */
    private $fieldsetRenderer;

    /**
     * @var ConditionsRenderer
     */
    private $conditionsRenderer;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param FormFactory $formFactory
     * @param FieldsetRenderer $fieldsetRenderer
     * @param ConditionsRenderer $conditionsRenderer
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        FormFactory $formFactory,
        FieldsetRenderer $fieldsetRenderer,
        ConditionsRenderer $conditionsRenderer,
        array $data = []
    ) {
        $this->fieldsetRenderer = $fieldsetRenderer;
        $this->conditionsRenderer = $conditionsRenderer;
        parent::__construct($context, $coreRegistry, $formFactory, $data);
    }

    /**
     * @return $this
     * @throws LocalizedException
     */
    protected function _prepareForm()
    {
        /** @var MethodRule $MethodRule */
        $MethodRule = $this->_coreRegistry->registry(RegistryConstants::CURRENT_SHIPPING_METHOD_RULE);
        $newConditionUrl = $this->getUrl('*/shipping_method_rule/newCondition/fieldset_id/' . self::FIELDSET_ID);

        /** @var Form $form */
        $form = $this->_formFactory->create();
        $form->setData('html_id_prefix', self::HTML_ID_PREFIX);

        $this->fieldsetRenderer->setTemplate('Magento_CatalogRule::promo/fieldset.phtml');
        $this->fieldsetRenderer->setData('field_set_id', self::FIELDSET_ID);
        $this->fieldsetRenderer->setData('new_child_url', $newConditionUrl);

        $conditionsFieldset = $form->addFieldset(
            self::FIELDSET_ID,
            [ 'legend' => __('Apply the rule only if the following conditions are met:') ]
        );

        $conditionsFieldset->setRenderer($this->fieldsetRenderer);

        $conditionsField = $conditionsFieldset->addField(
            RuleFormDataProvider::FIELD_CONDITIONS,
            'text',
            [
                'name' => RuleFormDataProvider::FIELD_CONDITIONS,
                'label' => __('Conditions'),
                'title' => __('Conditions'),
                'required' => true,
                'data-form-part' => RuleFormDataProvider::FORM_NAMESPACE,
            ]
        );

        $conditionsField->setRenderer($this->conditionsRenderer);
        $conditionsField->setData('rule', $MethodRule);

        $this->prepareRuleCondition($MethodRule->getConditions());
        $form->setValues($MethodRule->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * @param AbstractCondition $ruleCondition
     */
    private function prepareRuleCondition(AbstractCondition $ruleCondition)
    {
        $ruleCondition->setData('form_name', RuleFormDataProvider::FORM_NAMESPACE);

        if ($ruleCondition instanceof CombinedCondition) {
            $subConditions = $ruleCondition->getConditions();

            if (!empty($subConditions) && is_array($subConditions)) {
                foreach ($subConditions as $subCondition) {
                    $this->prepareRuleCondition($subCondition);
                }
            }
        }
    }
}
