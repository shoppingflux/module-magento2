<?php

namespace ShoppingFeed\Manager\Model\Account\Store\Config;

use Magento\Framework\Phrase;
use Magento\Ui\Component\Form\Field as FormField;
use ShoppingFeed\Manager\Model\Account\Store\Config\Value\AbstractHandler as ValueHandler;


abstract class AbstractField
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var ValueHandler
     */
    private $valueHandler;

    /**
     * @var string
     */
    private $label;

    /**
     * @var bool
     */
    private $isRequired;

    /**
     * @var mixed
     */
    private $defaultFormValue;

    /**
     * @var mixed
     */
    private $defaultUseValue;

    /**
     * @var Phrase|string
     */
    private $notice;

    /**
     * @var string[]
     */
    private $additionalValidationClasses;

    /**
     * @param string $name
     * @param ValueHandler $valueHandler
     * @param string $label
     * @param bool $isRequired
     * @param mixed|null $defaultFormValue
     * @param mixed|null $defaultUseValue
     * @param Phrase|string $notice
     * @param string[] $additionalValidationClasses
     */
    public function __construct(
        $name,
        ValueHandler $valueHandler,
        $label,
        $isRequired = false,
        $defaultFormValue = null,
        $defaultUseValue = null,
        $notice = '',
        array $additionalValidationClasses = []
    ) {
        $this->name = $name;
        $this->valueHandler = $valueHandler;
        $this->label = $label;
        $this->isRequired = (bool) $isRequired;
        $this->defaultFormValue = $defaultFormValue;
        $this->defaultUseValue = $defaultUseValue;
        $this->notice = $notice;
        $this->additionalValidationClasses = $additionalValidationClasses;
    }

    /**
     * @param string $fieldName
     * @return string
     */
    protected function getDependentFieldTarget($fieldName)
    {
        return (false === strpos($fieldName, '.')) ? '${$.parentName}.' . $fieldName : $fieldName;
    }

    /**
     * @param mixed $value
     * @param string[] $fieldNames
     * @param bool $isEnabled
     * @return array
     */
    protected function getSwitcherDependencyRule($value, array $fieldNames, $isEnabled)
    {
        $actions = [];

        foreach ($fieldNames as $fieldName) {
            $target = $this->getDependentFieldTarget($fieldName);

            $actions[] = [
                'target' => $target,
                'callback' => $isEnabled ? 'enable' : 'hide',
            ];

            $actions[] = [
                'target' => $target,
                'callback' => $isEnabled ? 'show' : 'disable',
            ];
        }

        return [
            'actions' => $actions,
            'value' => ('' !== $value) ? $value : null,
        ];
    }

    /**
     * @param array $dependencies
     * @param array $availableValues
     * @return array
     */
    protected function getSwitcherConfig(array $dependencies, array $availableValues)
    {
        $valueEnabledFields = [];
        $valueDisabledFields = [];

        foreach ($dependencies as $subDependencies) {
            if (is_array($subDependencies)) {
                $fieldNames = $subDependencies['field_names'] ?? [];
                $enabledValues = $subDependencies['values'] ?? [];
                $disabledValues = array_diff($availableValues, $enabledValues);

                foreach ($enabledValues as $value) {
                    $valueEnabledFields[$value] = array_merge($valueEnabledFields[$value] ?? [], $fieldNames);
                }

                foreach ($disabledValues as $value) {
                    $valueDisabledFields[$value] = array_merge($valueDisabledFields[$value] ?? [], $fieldNames);
                }
            }
        }

        $switcherRules = [];

        foreach ($valueEnabledFields as $value => $fieldNames) {
            $switcherRules[] = $this->getSwitcherDependencyRule($value, $fieldNames, true);
        }

        foreach ($valueDisabledFields as $value => $fieldNames) {
            $switcherRules[] = $this->getSwitcherDependencyRule($value, $fieldNames, false);
        }

        return [ 'enabled' => true, 'rules' => $switcherRules ];
    }

    /**
     * @return string
     */
    final public function getName()
    {
        return $this->name;
    }

    /**
     * @return ValueHandler
     */
    final public function getValueHandler()
    {
        return $this->valueHandler;
    }

    /**
     * @return bool
     */
    final public function isRequired()
    {
        return $this->isRequired;
    }

    /**
     * @return string
     */
    final public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return mixed|null
     */
    final public function getDefaultFormValue()
    {
        return $this->defaultFormValue;
    }

    /**
     * @return mixed|null
     */
    final public function getDefaultUseValue()
    {
        return $this->defaultUseValue;
    }

    /**
     * @return array
     */
    protected function getBaseUiMetaConfig()
    {
        $metaConfig = [
            'dataType' => $this->valueHandler->getFormDataType(),
            'label' => $this->label,
        ];

        if ($this->isRequired) {
            $metaConfig['validation'] = [ 'required-entry' => true ];
        }

        if (null !== $this->defaultFormValue) {
            $metaConfig['default'] = $this->defaultFormValue;
        }

        if (is_string($this->notice) || ($this->notice instanceof Phrase)) {
            $notice = (string) $this->notice;

            if ('' !== $notice) {
                $metaConfig['notice'] = $this->notice;
            }
        }

        $additionalValidationClasses = array_unique(
            array_merge(
                $this->additionalValidationClasses,
                $this->getValueHandler()->getFieldValidationClasses()
            )
        );

        if (!empty($additionalValidationClasses)) {
            $metaConfig['validation'] = array_merge(
                array_fill_keys($additionalValidationClasses, true),
                $metaConfig['validation'] ?? []
            );
        }

        return $metaConfig;
    }

    /**
     * @return array
     */
    public function getUiMetaConfig()
    {
        $metaConfig = $this->getBaseUiMetaConfig();

        return [
            'arguments' => [
                'data' => [
                    'config' => array_merge(
                        [
                            'componentType' => FormField::NAME,
                            'dataScope' => $this->getName(),
                            'visible' => true,
                        ],
                        $metaConfig
                    ),
                ],
            ],
            'attributes' => [
                'class' => FormField::class,
                'formElement' => $metaConfig['formElement'] ?? 'input',
                'name' => $this->getName(),
            ],
            'children' => [],
        ];
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function prepareRawValueForForm($value)
    {
        return $this->getValueHandler()
            ->prepareRawValueForForm(
                $value,
                $this->getDefaultFormValue(),
                $this->isRequired()
            );
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function prepareRawValueForUse($value)
    {
        return $this->getValueHandler()
            ->prepareRawValueForUse(
                $value,
                $this->getDefaultUseValue(),
                $this->isRequired()
            );
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function prepareFormValueForSave($value)
    {
        return $this->getValueHandler()
            ->prepareFormValueForSave($value, $this->isRequired());
    }
}
