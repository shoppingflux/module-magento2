<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Base\Edit;

use ShoppingFeed\Manager\Block\Adminhtml\AbstractButton;


class SaveAndContinueButton extends AbstractButton
{
    public function getButtonData()
    {
        return [
            'label' => __('Save and Continue Edit'),
            'class' => 'save',
            'data_attribute' => [
                'mage-init' => [ 'button' => [ 'event' => 'saveAndContinueEdit' ] ],
            ],
            'sort_order' => 8000,
        ];
    }
}
