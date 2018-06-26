<?php

namespace ShoppingFeed\Manager\Model\Config\Field;

use Magento\Ui\Component\Form\Element\Input as UiInput;
use ShoppingFeed\Manager\Model\Config\AbstractField;


class TextBox extends AbstractField
{
    const TYPE_CODE = 'text_box';

    public function getBaseUiMetaConfig()
    {
        return array_merge(
            parent::getBaseUiMetaConfig(),
            [ 'formElement' => UiInput::NAME ]
        );
    }
}
