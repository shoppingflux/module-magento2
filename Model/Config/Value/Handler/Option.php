<?php

namespace ShoppingFeed\Manager\Model\Config\Value\Handler;

use ShoppingFeed\Manager\Model\Config\Value\AbstractHandler;

class Option extends AbstractHandler
{
    const TYPE_CODE = 'option';

    /**
     * @var string
     */
    private $dataType;

    /**
     * @var array
     */
    private $optionArray;

    /**
     * @var array
     */
    private $optionValues;

    /**
     * @var bool
     */
    private $hasEmptyOption;

    /**
     * @var mixed
     */
    private $emptyOptionValue;

    /**
     * @param string $dataType
     * @param array $optionArray
     * @param bool $hasEmptyOption
     */
    public function __construct($dataType, array $optionArray, $hasEmptyOption = false)
    {
        $this->dataType = $dataType;
        $this->optionArray = $optionArray;
        $this->hasEmptyOption = (bool) $hasEmptyOption;
        $this->optionValues = $this->prepareOptionValues($optionArray);
    }

    /**
     * @param array $optionArray
     * @param int $depth
     * @return array
     */
    private function prepareOptionValues(array $optionArray, $depth = 1)
    {
        $optionValues = [];

        foreach ($optionArray as $option) {
            if (is_array($option)
                && array_key_exists('value', $option)
                && ($this->hasEmptyOption || !$this->isUndefinedValue($option['value']))
            ) {
                if (is_array($option['value'])) {
                    $subOptionArray = $this->prepareOptionValues($option['value'], $depth + 1);

                    if (empty($subOptionArray)) {
                        continue;
                    } else {
                        $optionValues = array_merge($optionValues, $subOptionArray);
                    }
                } else {
                    $optionValues[] = $option['value'];
                }

                if ((1 === $depth) && $this->isUndefinedValue($option['value'])) {
                    $this->emptyOptionValue = $option['value'];
                }
            }
        }

        return $optionValues;
    }

    public function getFormDataType()
    {
        return $this->dataType;
    }

    /**
     * @return array
     */
    public function getOptionArray()
    {
        return $this->optionArray;
    }

    /**
     * @return array
     */
    public function getOptionValues()
    {
        return $this->optionValues;
    }

    /**
     * @return bool
     */
    public function hasEmptyOption()
    {
        return $this->hasEmptyOption;
    }

    /**
     * @return mixed
     */
    public function getEmptyOptionValue()
    {
        return $this->emptyOptionValue;
    }

    /**
     * @param mixed $value
     * @return mixed|null
     */
    protected function getValidValue($value)
    {
        if (!$this->isUndefinedValue($value)) {
            $values = $this->getOptionValues();

            if (false !== array_search($value, $values, true)) {
                return $value;
            }

            $validKey = array_search($value, $values, false);

            if (false !== $validKey) {
                return $values[$validKey];
            }
        }

        return null;
    }

    public function prepareRawValueForForm($value, $defaultValue, $isRequired)
    {
        $value = $this->getValidValue($value);
        return (null !== $value) ? $value : $this->getValidValue($defaultValue);
    }

    public function prepareRawValueForUse($value, $defaultValue, $isRequired)
    {
        $value = $this->getValidValue($value);
        return (null !== $value) ? $value : $defaultValue;
    }

    public function prepareFormValueForSave($value, $isRequired)
    {
        return $this->getValidValue($value);
    }
}
