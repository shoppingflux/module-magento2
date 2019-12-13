<?php

namespace ShoppingFeed\Manager\Model\Config\Field;

use Magento\Ui\Component\Form\Element\Hidden as UiHidden;
use ShoppingFeed\Manager\Model\Config\AbstractField;

class Hidden extends AbstractField
{
    const TYPE_CODE = 'hidden';

    public function getBaseUiMetaConfig()
    {
        return array_merge(
            parent::getBaseUiMetaConfig(),
            [ 'formElement' => UiHidden::NAME ]
        );
    }
}
