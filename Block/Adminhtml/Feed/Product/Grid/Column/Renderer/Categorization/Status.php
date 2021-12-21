<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Feed\Product\Grid\Column\Renderer\Categorization;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\Options\Extended as OptionsRenderer;
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
        $categoryId = (int) $row->getData($this->getColumn()->getIndex());

        return empty($categoryId)
            ? '<strong>' . __('Non Exportable') . '</strong>'
            : '<strong>' . __('Exportable') . '</strong> (' . (string) parent::render($row) . ')';
    }
}
