<?php

namespace ShoppingFeed\Manager\Model\Account\Store\Config\Value\Handler;


class PositiveInteger extends Integer
{
    public function getFieldValidationClasses()
    {
        return array_merge(parent::getFieldValidationClasses(), [ self::VALIDATION_CLASS_GREATER_THAN_ZERO ]);
    }

    public function isValidValue($value, $isRequired)
    {
        return ($value > 0);
    }
}
