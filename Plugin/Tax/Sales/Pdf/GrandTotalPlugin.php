<?php

namespace ShoppingFeed\Manager\Plugin\Tax\Sales\Pdf;

use Magento\Tax\Model\Sales\Pdf\GrandTotal as PdfGrandTotal;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;

class GrandTotalPlugin
{
    public function afterGetTotalsForDisplay(PdfGrandTotal $subject, $totals)
    {
        if (!is_array($totals)) {
            return $totals;
        }

        $feesAmount = $subject->getOrder()
            ->getData(MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_MARKETPLACE_FEES_AMOUNT);

        if ($feesAmount > 0) {
            $amountPrefix = trim($subject->getAmountPrefix());
            $fontSize = (int) $subject->getFontSize();

            array_unshift(
                $totals,
                [
                    'amount' => $amountPrefix . $subject->getOrder()->formatPriceTxt($feesAmount),
                    'label' => __('Marketplace Fees') . ':',
                    'font_size' => $fontSize > 0 ? $fontSize : 7,
                ]
            );
        }

        return $totals;
    }
}
