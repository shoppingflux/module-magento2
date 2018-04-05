<?php

namespace ShoppingFeed\Manager\Model\Account\Store\Config\Value\Handler;

use ShoppingFeed\Manager\Model\Account\Store\Config\Value\AbstractHandler;


class Option extends AbstractHandler
{
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
     * @param string $dataType
     * @param array $optionArray
     * @param bool $hasEmptyOption
     */
    public function __construct($dataType, array $optionArray, $hasEmptyOption = false)
    {
        $this->dataType = $dataType;
        $this->optionArray = $optionArray;
        $this->optionValues = [];
        $this->hasEmptyOption = (bool) $hasEmptyOption;

        foreach ($optionArray as $option) {
            if (is_array($option)
                && array_key_exists('value', $option)
                && ($this->hasEmptyOption || !$this->isUndefinedValue($option['value']))
            ) {
                $this->optionValues[] = $option['value'];
            }
        }

        $this->optionValues = array_unique($this->optionValues);
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
