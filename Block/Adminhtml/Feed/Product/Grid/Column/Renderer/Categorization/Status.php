<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Feed\Product\Grid\Column\Renderer\Categorization;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\Options\Extended as OptionsRenderer;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\Text as TextRenderer;
use Magento\Framework\DataObject;

class Status extends OptionsRenderer
{
    public function _getOptions()
    {
        return $this->_converter->toTreeArray(
            $this->getColumn()->getDataByKey('category_options')
        );
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
