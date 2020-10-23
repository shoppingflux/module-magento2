<?php

namespace ShoppingFeed\Manager\Model\Sales\Quote\Address\Total;

use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;

class BundleAdjustmentTax extends AbstractTotal
{
    public function collect(Quote $quote, ShippingAssignmentInterface $shippingAssignment, Total $total)
    {
        parent::collect($quote, $shippingAssignment, $total);

        $taxableDetails = $total->getExtraTaxableDetails();
        $typeKey = BundleAdjustment::TAXABLE_TYPE;
        $itemKey = CommonTaxCollector::ASSOCIATION_ITEM_CODE_FOR_QUOTE;

        $adjustmentExclTax = 0;
        $adjustmentInclTax = 0;
        $baseAdjustmentExclTax = 0;
        $baseAdjustmentInclTax = 0;

        if (
            is_array($taxableDetails)
            && isset($taxableDetails[$typeKey][$itemKey])
            && is_array($taxableDetails[$typeKey][$itemKey])
        ) {
            foreach ($taxableDetails[$typeKey][$itemKey] as $adjustmentDetails) {
                $adjustmentExclTax += $adjustmentDetails[CommonTaxCollector::KEY_TAX_DETAILS_ROW_TOTAL] ?? 0.0;
                $adjustmentInclTax += $adjustmentDetails[CommonTaxCollector::KEY_TAX_DETAILS_ROW_TOTAL_INCL_TAX] ?? 0.0;
                $baseAdjustmentExclTax += $adjustmentDetails[CommonTaxCollector::KEY_TAX_DETAILS_BASE_ROW_TOTAL] ?? 0.0;
                $baseAdjustmentInclTax += $adjustmentDetails[CommonTaxCollector::KEY_TAX_DETAILS_BASE_ROW_TOTAL_INCL_TAX] ?? 0.0;
            }
        }

        $total->setTotalAmount(BundleAdjustment::TAXABLE_TYPE, $adjustmentExclTax);
        $total->setBaseTotalAmount(BundleAdjustment::TAXABLE_TYPE, $baseAdjustmentExclTax);

        $total->addData(
            [
                MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_BUNDLE_ADJUSTMENT => $adjustmentExclTax,
                MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_BUNDLE_ADJUSTMENT_INCL_TAX => $adjustmentInclTax,
                MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_BASE_BUNDLE_ADJUSTMENT => $baseAdjustmentExclTax,
                MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_BASE_BUNDLE_ADJUSTMENT_INCL_TAX => $baseAdjustmentInclTax,
            ]
        );

        return $this;
    }
}
