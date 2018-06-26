<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Shipping\Method\Rule\Edit;


class SaveButton extends AbstractButton
{
    public function getButtonData()
    {
        return [
            'label' => __('Save Rule'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => [ 'button' => [ 'event' => 'save' ] ],
                'form-role' => 'save',
            ],
            'sort_order' => 9000,
        ];
    }
}
