<?php

namespace ShoppingFeed\Manager\Model\Config\Value;

abstract class AbstractHandler implements HandlerInterface
{
    const VALIDATION_CLASS_DIGITS = 'validate-digits';
    const VALIDATION_CLASS_GREATER_THAN_ZERO = 'validate-greater-than-zero';
    const VALIDATION_CLASS_NUMBER = 'validate-number';

    abstract public function getFormDataType();

    public function getFieldValidationClasses()
    {
        return [];
    }

    public function isUndefinedValue($value)
    {
        return (null === $value) || ('' === $value);
    }

    public function isEqualValues($valueA, $valueB)
    {
        return $valueA === $valueB;
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

    public function prepareRawValueForForm($value, $defaultValue, $isRequired)
    {
        return !$this->isUndefinedValue($value)
            ? $this->prepareValue($value)
            : ($isRequired ? $defaultValue : null);
    }

    public function prepareRawValueForUse($value, $defaultValue, $isRequired)
    {
        return !$this->isUndefinedValue($value) && $this->isValidValue($value, $isRequired)
            ? $this->prepareValue($value)
            : $defaultValue;
    }

    public function prepareFormValueForSave($value, $isRequired)
    {
        return !$this->isUndefinedValue($value) ? $this->prepareValue($value) : null;
    }
}
