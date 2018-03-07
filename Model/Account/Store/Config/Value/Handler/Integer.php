<?php

namespace ShoppingFeed\Manager\Model\Account\Store\Config\Value\Handler;

use ShoppingFeed\Manager\Model\Account\Store\Config\Value\AbstractHandler;


class Integer extends AbstractHandler
{
    public function getFormDataType()
    {
        return 'number';
    }

    public function getFieldValidationClasses()
    {
        return [ self::VALIDATION_CLASS_DIGITS ];
    }

    protected function prepareValue($value)
    {
        return (int) $value;
    }
}
