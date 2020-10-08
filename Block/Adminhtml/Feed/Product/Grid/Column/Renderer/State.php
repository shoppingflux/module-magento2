<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Feed\Product\Grid\Column\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\Options as OptionsRenderer;
use Magento\Framework\DataObject;

class State extends OptionsRenderer
{
    /**
     * @param string $date
     * @return string
     */
    private function formatDateValue($date)
    {
        return $this->_localeDate->formatDateTime(
            $date,
            \IntlDateFormatter::SHORT,
            \IntlDateFormatter::SHORT
        );
    }

    public function render(DataObject $row)
    {
        $value = '<div><strong>' . parent::render($row) . '</strong></div>';
        $untilDateIndex = trim($this->getColumn()->getDataByKey('until_date_index'));
        $refreshDateIndex = trim($this->getColumn()->getDataByKey('refresh_date_index'));

        if (!empty($untilDateIndex) && ($untilDate = $row->getDataByKey($untilDateIndex))) {
            $untilDate = $this->formatDateValue($untilDate);
            $value .= '<div><em>' . $this->escapeHtml(__('Until:')) . '</em></div>';
            $value .= '<div>' . $this->escapeHtml($untilDate) . '</div>';
        }

        if (!empty($refreshDateIndex)) {
            if ($refreshDate = $row->getDataByKey($refreshDateIndex)) {
                $refreshDate = $this->formatDateValue($refreshDate);
            } else {
                $refreshDate = __('Never');
            }

            $value .= '<div><em>' . $this->escapeHtml(__('Last Update:')) . '</em></div>';
            $value .= '<div>' . $this->escapeHtml($refreshDate) . '</div>';
        }

        return $value;
    }
}
