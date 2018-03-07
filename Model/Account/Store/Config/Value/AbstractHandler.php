<?php

namespace ShoppingFeed\Manager\Model\Account\Store\Config\Value;


abstract class AbstractHandler
{
    const VALIDATION_CLASS_DIGITS = 'validate-digits';
    const VALIDATION_CLASS_GREATER_THAN_ZERO = 'validate-greater-than-zero';
    const VALIDATION_CLASS_NUMBER = 'validate-number';

    /**
     * @return string
     */
    abstract public function getFormDataType();

    /**
     * @return array
     */
    public function getFieldValidationClasses()
    {
        return [];
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function isUndefinedValue($value)
    {
        return (null === $value) || ('' === $value);
    }

    /**
     * @param mixed $value
     * @param bool $isRequired
     * @return bool
     */
    protected function isValidValue($value, $isRequired)
    {
        return true;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function prepareValue($value)
    {
        return $value;
    }

    /**
     * @param mixed $value
     * @param mixed $defaultValue
     * @param bool $isRequired
     * @return mixed
     */
    public function prepareRawValueForForm($value, $defaultValue, $isRequired)
    {
        return !$this->isUndefinedValue($value)
            ? $this->prepareValue($value)
            : ($isRequired ? $defaultValue : null);
    }

    /**
     * @param mixed $value
     * @param mixed $defaultValue
     * @param bool $isRequired
     * @return mixed
     */
    public function prepareRawValueForUse($value, $defaultValue, $isRequired)
    {
        return !$this->isUndefinedValue($value) && $this->isValidValue($value, $isRequired)
            ? $this->prepareValue($value)
            : $defaultValue;
    }

    /**
     * @param mixed $value
     * @param bool $isRequired
     * @return mixed
     */
    public function prepareFormValueForSave($value, $isRequired)
    {
        return !$this->isUndefinedValue($value) ? $this->prepareValue($value) : null;
    }
}
