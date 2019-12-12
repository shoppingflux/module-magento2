<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Feed\Product\Grid\Column\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\Options as OptionsRenderer;
use Magento\Framework\DataObject;

class State extends OptionsRenderer
{
    public function render(DataObject $row)
    {
        $value = '<div><strong>' . parent::render($row) . '</strong></div>';
        $dateIndex = trim($this->getColumn()->getDataByKey('refresh_date_index'));

        if (!empty($dateIndex)) {
            if ($date = $row->getDataByKey($dateIndex)) {
                $refreshDate = $this->_localeDate->formatDateTime(
                    $date,
                    \IntlDateFormatter::SHORT,
                    \IntlDateFormatter::SHORT,
                    null,
                    'UTC'
                );
            } else {
                $refreshDate = __('Never');
            }

            $value .= '<div><em>' . $this->escapeHtml(__('Last Update:')) . '</em></div>';
            $value .= '<div>' . $this->escapeHtml($refreshDate) . '</div>';
        }

        return $value;
    }
}
