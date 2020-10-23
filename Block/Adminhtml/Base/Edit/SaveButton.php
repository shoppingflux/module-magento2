<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Base\Edit;

use ShoppingFeed\Manager\Block\Adminhtml\AbstractButton;

class SaveButton extends AbstractButton
{
    public function getButtonData()
    {
        return !$this->isAllowed()
            ? []
            : [
                'name' => $this->getName('save'),
                'label' => $this->getLabel('Save'),
                'class' => $this->getClass('save primary'),
                'sort_order' => $this->getSortOrder(9000),
                'data_attribute' => [
                    'mage-init' => [
                        'button' => [
                            'event' => 'save',
                        ],
                    ],
                    'form-role' => 'save',
                ],
            ];
    }
}
