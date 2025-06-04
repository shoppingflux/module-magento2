<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Account\Edit;

use ShoppingFeed\Manager\Block\Adminhtml\AbstractButton;

class BackButton extends AbstractButton
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        return !$this->isAllowed()
            ? []
            : [
                'name' => $this->getName('back'),
                'label' => $this->getLabel('Back'),
                'class' => $this->getClass('back'),
                'sort_order' => $this->getSortOrder(1000),
                'on_click' => sprintf("location.href = '%s';", $this->getUrl('*/account_store/')),
            ];
    }
}
