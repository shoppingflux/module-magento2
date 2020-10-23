<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Base\Edit;

use ShoppingFeed\Manager\Block\Adminhtml\AbstractButton;

class ResetButton extends AbstractButton
{
    public function getButtonData()
    {
        return !$this->isAllowed()
            ? []
            : [
                'name' => $this->getName('reset'),
                'label' => $this->getLabel('Reset'),
                'class' => $this->getClass('reset'),
                'on_click' => 'location.reload();',
                'sort_order' => $this->getSortOrder(3000),
            ];
    }
}
