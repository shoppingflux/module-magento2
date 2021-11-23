<?php

namespace ShoppingFeed\Manager\Model\Config\Value\Handler;

class NonNegativeNumber extends Number
{
    const TYPE_CODE = 'non_negative_number';

    public function getFieldValidationClasses()
    {
        return [ self::VALIDATION_CLASS_ZERO_OR_GREATER ];
    }

    public function isValidValue($value, $isRequired)
    {
        return ($value >= 0);
    }
}
