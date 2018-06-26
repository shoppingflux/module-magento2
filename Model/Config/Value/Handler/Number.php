<?php

namespace ShoppingFeed\Manager\Model\Config\Value\Handler;

use Magento\Ui\Component\Form\Element\DataType\Number as UiNumber;
use ShoppingFeed\Manager\Model\Config\Value\AbstractHandler;


class Number extends AbstractHandler
{
    const TYPE_CODE = 'number';
    
    public function getFormDataType()
    {
        return UiNumber::NAME;
    }

    public function getFieldValidationClasses()
    {
        return [ self::VALIDATION_CLASS_NUMBER ];
    }

    protected function prepareValue($value)
    {
        return (float) $value;
    }
}
