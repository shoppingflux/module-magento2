<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Base\Edit;

use ShoppingFeed\Manager\Block\Adminhtml\AbstractButton;

class BackButton extends AbstractButton
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Back'),
            'on_click' => sprintf("location.href = '%s';", $this->getBackUrl()),
            'class' => 'back',
            'sort_order' => 1000,
        ];
    }

    /**
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('*/*/');
    }
}
