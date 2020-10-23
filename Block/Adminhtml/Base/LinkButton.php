<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Base;

use ShoppingFeed\Manager\Block\Adminhtml\AbstractButton;

class LinkButton extends AbstractButton
{
    public function getButtonData()
    {
        return !$this->isAllowed()
            ? []
            : [
                'name' => $this->getName('link'),
                'label' => $this->getLabel('Link'),
                'class' => $this->getClass('primary'),
                'sort_order' => $this->getSortOrder(),
                'on_click' => sprintf("location.href = '%s';", $this->getUrl('*/*/*')),
            ];
    }
}
