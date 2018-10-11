<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Base\Edit;

use ShoppingFeed\Manager\Block\Adminhtml\AbstractButton;

class SaveButton extends AbstractButton
{
    public function getButtonData()
    {
        return [
            'label' => $this->getLabel(__('Save')),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => [ 'button' => [ 'event' => 'save' ] ],
                'form-role' => 'save',
            ],
            'sort_order' => 9000,
        ];
    }
}
