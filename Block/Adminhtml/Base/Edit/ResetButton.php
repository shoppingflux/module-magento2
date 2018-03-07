<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Base\Edit;

use ShoppingFeed\Manager\Block\Adminhtml\AbstractButton;


class ResetButton extends AbstractButton
{
    public function getButtonData()
    {
        return [
            'label' => __('Reset'),
            'class' => 'reset',
            'on_click' => 'location.reload();',
            'sort_order' => 3000,
        ];
    }
}
