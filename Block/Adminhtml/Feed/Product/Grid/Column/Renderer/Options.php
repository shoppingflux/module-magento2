<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Feed\Product\Grid\Column\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\Options\Extended as BaseRenderer;
use Magento\Framework\Phrase;

class Options extends BaseRenderer
{
    public function _getOptions()
    {
        $options = parent::_getOptions();

        foreach ($options as $key => $option) {
            if (is_array($option)) {
                if (($option['value'] ?? null) instanceof Phrase) {
                    $options[$key]['value'] = (string) $option['value'];
                }

                if (($option['label'] ?? null) instanceof Phrase) {
                    $options[$key]['label'] = (string) $option['label'];
                }
            } elseif ($option instanceof Phrase) {
                $options[$key] = (string) $option;
            }
        }

        return $options;
    }
}
