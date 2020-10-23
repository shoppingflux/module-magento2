<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Base\Edit;

use ShoppingFeed\Manager\Block\Adminhtml\AbstractButton;

class SaveAndContinueButton extends AbstractButton
{
    public function getButtonData()
    {
        return !$this->isAllowed()
            ? []
            : [
                'name' => $this->getName('save_and_continue'),
                'label' => $this->getLabel('Save and Continue Edit'),
                'class' => $this->getClass('save'),
                'sort_order' => $this->getSortOrder(8000),
                'data_attribute' => [
                    'mage-init' => [
                        'button' => [
                            'event' => 'saveAndContinueEdit',
                        ],
                    ],
                ],
            ];
    }
}
