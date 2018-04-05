<?php

namespace ShoppingFeed\Manager\Model\Account\Store\Config\Value\Handler;

use Magento\Ui\Component\Form\Element\DataType\Text as UiText;
use ShoppingFeed\Manager\Model\Account\Store\Config\Value\AbstractHandler;


class Text extends AbstractHandler
{
    public function getFormDataType()
    {
        return UiText::NAME;
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
