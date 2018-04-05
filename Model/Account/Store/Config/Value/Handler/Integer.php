<?php

namespace ShoppingFeed\Manager\Model\Account\Store\Config\Value\Handler;

use Magento\Ui\Component\Form\Element\DataType\Number as UiNumber;
use ShoppingFeed\Manager\Model\Account\Store\Config\Value\AbstractHandler;


class Integer extends AbstractHandler
{
    public function getFormDataType()
    {
        return UiNumber::NAME;
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
