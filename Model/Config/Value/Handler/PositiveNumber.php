<?php

namespace ShoppingFeed\Manager\Model\Config\Value\Handler;

class PositiveNumber extends Number
{
    const TYPE_CODE = 'positive_number';

    public function getFieldValidationClasses()
    {
        return [ self::VALIDATION_CLASS_GREATER_THAN_ZERO ];
    }

    public function isValidValue($value, $isRequired)
    {
        return ($value > 0);
    }
}
