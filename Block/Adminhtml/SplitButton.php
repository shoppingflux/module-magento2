<?php

namespace ShoppingFeed\Manager\Block\Adminhtml;

use Magento\Ui\Component\Control\SplitButton as BaseSplitButton;

class SplitButton extends BaseSplitButton
{
    protected function attributesToHtml($attributes)
    {
        // Remove the "primary" class if it was not explicitly required.
        if (is_array($attributes) && isset($attributes['class'])) {
            if (!preg_match('/\bprimary\b/', $this->getClass())) {
                $attributes['class'] = preg_replace('/\bprimary\b/', '', $attributes['class']);
            }
        }

        return parent::attributesToHtml($attributes);
    }
}
