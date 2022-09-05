<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Feed\Product\Grid\Column\Renderer\Categorization;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\Text as TextRenderer;
use Magento\Framework\DataObject;
use Magento\Framework\Phrase;
use ShoppingFeed\Manager\Block\Adminhtml\Feed\Product\Grid\Column\Renderer\Options as OptionsRenderer;

class Status extends OptionsRenderer
{
    public function _getOptions()
    {
        $options = $this->_converter->toTreeArray(
            $this->getColumn()->getDataByKey('category_options')
        );

        foreach ($options as $key => $option) {
            if (is_array($option) && (($option['value'] ?? null) instanceof Phrase)) {
                $options[$key]['value'] = (string) $option['value'];
            } elseif ($option instanceof Phrase) {
                $options[$key] = (string) $option;
            }
        }

        return $options;
    }

    public function render(DataObject $row)
    {
        $options = $this->_getOptions();

        if (is_array($options) && !empty($options)) {
            $categoryId = (int) $row->getData($this->getColumn()->getIndex());
            $categoryLabel = $categoryId ? (string) parent::render($row) : null;
        } else {
            $categoryLabel = trim((string) TextRenderer::render($row));
            $categoryLabel = ('' === $categoryLabel) ? null : $categoryLabel;
        }

        return (null === $categoryLabel)
            ? '<strong>' . __('Not Exportable') . '</strong>'
            : '<strong>' . __('Exportable') . '</strong> (' . $categoryLabel . ')';
    }
}
