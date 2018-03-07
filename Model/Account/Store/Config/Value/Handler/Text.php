<?php

namespace ShoppingFeed\Manager\Model\Account\Store\Config\Value\Handler;

use ShoppingFeed\Manager\Model\Account\Store\Config\Value\AbstractHandler;


class Text extends AbstractHandler
{
    public function getFormDataType()
    {
        return 'text';
    }

    public function isUndefinedValue($value)
    {
        return (null === $value);
    }

    protected function isValidValue($value, $isRequired)
    {
        return !$isRequired || ($value !== '');
    }

    protected function prepareValue($value)
    {
        return (string) $value;
    }
}
