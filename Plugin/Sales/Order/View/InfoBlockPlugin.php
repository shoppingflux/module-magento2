<?php

namespace ShoppingFeed\Manager\Plugin\Sales\Order\View;

use Magento\Sales\Block\Adminhtml\Order\View\Info as InfoBlock;

class InfoBlockPlugin
{
    public function afterToHtml(InfoBlock $subject, $result)
    {
        if ($marketplaceBlock = $subject->getLayout()->getBlock('sfm_order_marketplace_info')) {
            $result .= $marketplaceBlock->toHtml();
        }

        return $result;
    }
}
