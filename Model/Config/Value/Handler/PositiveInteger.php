<?php

namespace ShoppingFeed\Manager\Model\Config\Value\Handler;


class PositiveInteger extends Integer
{
    const TYPE_CODE = 'positive_integer';
    
    public function getFieldValidationClasses()
    {
        return array_merge(parent::getFieldValidationClasses(), [ self::VALIDATION_CLASS_GREATER_THAN_ZERO ]);
    }

    public function isValidValue($value, $isRequired)
    {
        return ($value > 0);
    }
}
