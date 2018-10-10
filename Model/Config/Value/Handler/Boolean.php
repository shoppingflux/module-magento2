<?php

namespace ShoppingFeed\Manager\Model\Config\Value\Handler;

use Magento\Ui\Component\Form\Element\DataType\Boolean as UiBoolean;
use ShoppingFeed\Manager\Model\Config\Value\AbstractHandler;

class Boolean extends AbstractHandler
{
    const TYPE_CODE = 'boolean';

    public function getFormDataType()
    {
        return UiBoolean::NAME;
    }

    public function prepareRawValueForForm($value, $defaultValue, $isRequired)
    {
        return (!$this->isUndefinedValue($value) ? $value : $defaultValue) ? 1 : 0;
    }

    public function prepareRawValueForUse($value, $defaultValue, $isRequired)
    {
        return !$this->isUndefinedValue($value) ? ($value ? true : false) : $defaultValue;
    }

    public function prepareFormValueForSave($value, $isRequired)
    {
        return $isRequired || !$this->isUndefinedValue($value) ? (bool) $value : null;
    }
}
