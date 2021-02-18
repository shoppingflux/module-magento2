<?php

namespace ShoppingFeed\Manager\Model\Config\Field;

use Magento\Framework\Phrase;
use Magento\Ui\Component\Form\Element\MultiSelect as UiMultiSelect;
use ShoppingFeed\Manager\Model\Config\Value\Handler\Option as OptionHandler;

class MultiSelect extends Select
{
    const TYPE_CODE = 'multi_select';
    const ALL_OPTION_VALUE = '___sfm_all___';
    const NONE_OPTION_VALUE = '___sfm_none___';

    /**
     * @var int
     */
    private $size;

    /**
     * @var bool
     */
    private $allowAll;

    /**
     * @param DependencyFactory $dependencyFactory
     * @param string $name
     * @param OptionHandler $valueHandler
     * @param string $label
     * @param bool $isRequired
     * @param mixed|null $defaultFormValue
     * @param mixed|null $defaultUseValue
     * @param Phrase|string $notice
     * @param array $dependencies
     * @param int $size
     * @param bool $allowAll
     * @param int|null $sortOrder
     */
    public function __construct(
        DependencyFactory $dependencyFactory,
        $name,
        OptionHandler $valueHandler,
        $label,
        $isRequired = false,
        $defaultFormValue = null,
        $defaultUseValue = null,
        $notice = '',
        array $dependencies = [],
        $size = 5,
        $allowAll = false,
        $sortOrder = null
    ) {
        $this->size = (int) $size;
        $this->allowAll = (bool) $allowAll;

        parent::__construct(
            $dependencyFactory,
            $name,
            $valueHandler,
            $label,
            $isRequired,
            $defaultFormValue,
            $defaultUseValue,
            $notice,
            $dependencies,
            $sortOrder
        );
    }

    protected function getEmptyOption()
    {
        return $this->isRequired() ? false : [ 'value' => self::NONE_OPTION_VALUE, 'label' => __('None') ];
    }

    /**
     * @return array
     */
    protected function getAllOptions()
    {
        $valueHandler = $this->getValueHandler();
        $options = $valueHandler->getOptionArray();

        if ($this->allowAll) {
            array_unshift($options, [ 'value' => self::ALL_OPTION_VALUE, 'label' => __('All') ]);
        }

        if (!$valueHandler->hasEmptyOption()) {
            array_unshift($options, $this->getEmptyOption());
        }

        return $options;
    }

    /**
     * @return array
     */
    protected function getAllOptionValues()
    {
        $valueHandler = $this->getValueHandler();
        $optionValues = $valueHandler->getOptionValues();

        if ($this->allowAll) {
            $optionValues[] = self::ALL_OPTION_VALUE;
        }

        if (!$valueHandler->hasEmptyOption()) {
            $optionValues[] = $this->getEmptyOptionValue();
        }

        return $optionValues;
    }

    public function getBaseUiMetaConfig()
    {
        return array_merge(
            parent::getBaseUiMetaConfig(),
            [ 'formElement' => UiMultiSelect::NAME, 'size' => $this->size ]
        );
    }

    public function isEqualValues($valueA, $valueB)
    {
        if (is_array($valueA) && is_array($valueB)) {
            sort($valueA);
            sort($valueB);
        }

        return $valueA === $valueB;
    }

    /**
     * @param mixed $value
     * @param mixed $defaultValue
     * @param string $handlerPrepareMethod
     * @return array
     */
    protected function prepareRawValue($value, $defaultValue, $handlerPrepareMethod)
    {
        $isRequired = $this->isRequired();
        $valueHandler = $this->getValueHandler();

        if (!is_array($value)) {
            $value = is_array($defaultValue) ? $defaultValue : [];
        }

        foreach ($value as $key => $subValue) {
            $subValue = $valueHandler->$handlerPrepareMethod($subValue, null, $isRequired);

            if (null !== $subValue) {
                $value[$key] = $subValue;
            } else {
                unset($value[$key]);
            }
        }

        return array_values($value);
    }

    public function prepareRawValueForForm($value)
    {
        $value = (array) $value;

        if ($this->allowAll && in_array(self::ALL_OPTION_VALUE, $value, true)) {
            return [ self::ALL_OPTION_VALUE ];
        }

        $value = $this->prepareRawValue($value, [], 'prepareRawValueForForm');

        // Default values will be selected when an empty value is returned for a required field,
        // but returning an invalid value to avoid this allows the form to be saved without selecting any valid value.

        return empty($value) && !$this->isRequired() ? [ self::NONE_OPTION_VALUE ] : $value;
    }

    public function prepareRawValueForUse($value)
    {
        $value = (array) $value;

        if ($this->allowAll && in_array(self::ALL_OPTION_VALUE, $value, true)) {
            return $this->getValueHandler()->getOptionValues();
        }

        return $this->prepareRawValue($value, $this->getDefaultUseValue(), 'prepareRawValueForUse');
    }

    public function prepareFormValueForSave($value)
    {
        if (is_array($value)) {
            $isRequired = $this->isRequired();
            $valueHandler = $this->getValueHandler();

            if (in_array(self::ALL_OPTION_VALUE, $value, true)) {
                $value = [ self::ALL_OPTION_VALUE ];
            }

            foreach ($value as $key => $subValue) {
                if (!$this->allowAll || ($subValue !== self::ALL_OPTION_VALUE)) {
                    if (!in_array($subValue, [ self::NONE_OPTION_VALUE, self::ALL_OPTION_VALUE ], true)) {
                        $subValue = $valueHandler->prepareFormValueForSave($subValue, $isRequired);
                    } else {
                        $subValue = null;
                    }
                }

                if (null !== $subValue) {
                    $value[$key] = $subValue;
                } else {
                    unset($value[$key]);
                }
            }

            return array_values($value);
        }

        return [];
    }
}
