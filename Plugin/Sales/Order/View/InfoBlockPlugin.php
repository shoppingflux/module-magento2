<?php

namespace ShoppingFeed\Manager\Plugin\Sales\Order\View;

use Magento\Sales\Block\Adminhtml\Order\View\Info as InfoBlock;

class InfoBlockPlugin
{
    const NON_ORDER_VIEW_TEMPLATES = [
        'Wyomind_EstimatedDeliveryDate::sales/order/view/estimated_delivery_date.phtml',
    ];

    public function afterToHtml(InfoBlock $subject, $result)
    {
        if (
            ($marketplaceBlock = $subject->getLayout()->getBlock('sfm_order_marketplace_info'))
            && !in_array($subject->getTemplate(), self::NON_ORDER_VIEW_TEMPLATES, true)
        ) {
            $result .= $marketplaceBlock->toHtml();
        }

        return $result;
    }
}
