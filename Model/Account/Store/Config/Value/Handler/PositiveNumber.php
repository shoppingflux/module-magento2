<?php

namespace ShoppingFeed\Manager\Model\Account\Store\Config\Value\Handler;


class PositiveNumber extends Number
{
    public function getFieldValidationClasses()
    {
        return [ self::VALIDATION_CLASS_GREATER_THAN_ZERO ];
    }

    public function isValidValue($value, $isRequired)
    {
        return ($value > 0);
    }
}
