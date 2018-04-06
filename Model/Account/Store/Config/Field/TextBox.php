<?php

namespace ShoppingFeed\Manager\Model\Account\Store\Config\Field;

use Magento\Ui\Component\Form\Element\Input as UiInput;
use ShoppingFeed\Manager\Model\Account\Store\Config\AbstractField;


class TextBox extends AbstractField
{
    public function getBaseUiMetaConfig()
    {
        return array_merge(
            parent::getBaseUiMetaConfig(),
            [ 'formElement' => UiInput::NAME ]
        );
    }
}
