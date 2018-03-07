<?php

namespace ShoppingFeed\Manager\Model\Account\Store\Config\Field;

use ShoppingFeed\Manager\Model\Account\Store\Config\AbstractField;


class TextBox extends AbstractField
{
    public function getMetaConfig()
    {
        return array_merge(
            parent::getMetaConfig(),
            [ 'formElement' => 'input' ]
        );
    }
}
