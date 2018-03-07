<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Account\Edit;


class SaveButton extends AbstractButton
{
    public function getButtonData()
    {
        return [
            'label' => __('Save Account'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => [ 'button' => [ 'event' => 'save' ] ],
                'form-role' => 'save',
            ],
            'sort_order' => 9000,
        ];
    }
}
