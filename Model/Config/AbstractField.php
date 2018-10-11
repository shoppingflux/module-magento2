<?php

namespace ShoppingFeed\Manager\Model\Config;

use Magento\Framework\Phrase;
use Magento\Ui\Component\Form\Field as UiField;
use ShoppingFeed\Manager\Model\Config\Field\Dependency;
use ShoppingFeed\Manager\Model\Config\Value\HandlerInterface as ValueHandlerInterface;

abstract class AbstractField implements FieldInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var ValueHandlerInterface
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
     * @var int|null
     */
    private $sortOrder;

    /**
     * @param string $name
     * @param ValueHandlerInterface $valueHandler
     * @param string $label
     * @param bool $isRequired
     * @param mixed|null $defaultFormValue
     * @param mixed|null $defaultUseValue
     * @param Phrase|string $notice
     * @param string[] $additionalValidationClasses
     * @param int|null $sortOrder
     */
    public function __construct(
        $name,
        ValueHandlerInterface $valueHandler,
        $label,
        $isRequired = false,
        $defaultFormValue = null,
        $defaultUseValue = null,
        $notice = '',
        array $additionalValidationClasses = [],
        $sortOrder = null
    ) {
        $this->name = $name;
        $this->valueHandler = $valueHandler;
        $this->label = $label;
        $this->isRequired = (bool) $isRequired;
        $this->defaultFormValue = $defaultFormValue;
        $this->defaultUseValue = $defaultUseValue;
        $this->notice = $notice;
        $this->additionalValidationClasses = $additionalValidationClasses;
        $this->sortOrder = $sortOrder;
    }

    /**
     * @param string $fieldName
     * @return string
     */
    private function getDependentFieldTarget($fieldName)
    {
        return (false === strpos($fieldName, '.')) ? '${$.parentName}.' . $fieldName : $fieldName;
    }

    /**
     * @param mixed $value
     * @param string[] $fieldNames
     * @param bool $isEnabled
     * @return array
     */
    private function getSwitcherDependencyRule($value, array $fieldNames, $isEnabled)
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
     * @param Dependency[] $dependencies
     * @param array $allValues
     * @return array
     */
    protected function getSwitcherConfig(array $dependencies, array $allValues)
    {
        $valueEnabledFields = [];
        $valueDisabledFields = [];

        foreach ($dependencies as $dependency) {
            $fieldNames = $dependency->getFieldNames();
            $enabledValues = $dependency->getValues();
            $disabledValues = array_diff($allValues, $enabledValues);

            foreach ($enabledValues as $value) {
                $valueEnabledFields[$value] = array_merge($valueEnabledFields[$value] ?? [], $fieldNames);
            }

            foreach ($disabledValues as $value) {
                $valueDisabledFields[$value] = array_merge($valueDisabledFields[$value] ?? [], $fieldNames);
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

    final public function getName()
    {
        return $this->name;
    }

    final public function getValueHandler()
    {
        return $this->valueHandler;
    }

    final public function getLabel()
    {
        return $this->label;
    }

    final public function isRequired()
    {
        return $this->isRequired;
    }

    final public function getDefaultFormValue()
    {
        return $this->defaultFormValue;
    }

    final public function getDefaultUseValue()
    {
        return $this->defaultUseValue;
    }

    final public function getSortOrder()
    {
        return $this->sortOrder;
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

        if (null !== $this->sortOrder) {
            $metaConfig['sortOrder'] = (int) $this->sortOrder;
        }

        return $metaConfig;
    }

    public function getUiMetaConfig()
    {
        $metaConfig = $this->getBaseUiMetaConfig();

        return [
            'arguments' => [
                'data' => [
                    'config' => array_merge(
                        [
                            'componentType' => UiField::NAME,
                            'dataScope' => $this->getName(),
                            'visible' => true,
                        ],
                        $metaConfig
                    ),
                ],
            ],
            'attributes' => [
                'class' => UiField::class,
                'formElement' => $metaConfig['formElement'] ?? 'input',
                'name' => $this->getName(),
            ],
            'children' => [],
        ];
    }

    public function prepareRawValueForForm($value)
    {
        return $this->getValueHandler()
            ->prepareRawValueForForm(
                $value,
                $this->getDefaultFormValue(),
                $this->isRequired()
            );
    }

    public function prepareRawValueForUse($value)
    {
        return $this->getValueHandler()
            ->prepareRawValueForUse(
                $value,
                $this->getDefaultUseValue(),
                $this->isRequired()
            );
    }

    public function prepareFormValueForSave($value)
    {
        return $this->getValueHandler()
            ->prepareFormValueForSave($value, $this->isRequired());
    }
}
