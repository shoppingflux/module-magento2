<?php

namespace ShoppingFeed\Manager\Model\Sales\Invoice\Total;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;

class BundleAdjustment extends AbstractTotal
{
    public function collect(Invoice $invoice)
    {
        parent::collect($invoice);

        $order = $invoice->getOrder();

        $adjustmentExclTax = $order->getData(
            MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_BUNDLE_ADJUSTMENT
        );

        $adjustmentInclTax = $order->getData(
            MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_BUNDLE_ADJUSTMENT_INCL_TAX
        );

        $baseAdjustmentExclTax = $order->getData(
            MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_BASE_BUNDLE_ADJUSTMENT
        );

        $baseAdjustmentInclTax = $order->getData(
            MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_BASE_BUNDLE_ADJUSTMENT_INCL_TAX
        );

        if (
            (null !== $adjustmentExclTax)
            && (null !== $adjustmentInclTax)
            && (null !== $baseAdjustmentExclTax)
            && (null !== $baseAdjustmentInclTax)
        ) {
            $invoice->addData(
                [
                    MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_BUNDLE_ADJUSTMENT => $adjustmentExclTax,
                    MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_BUNDLE_ADJUSTMENT_INCL_TAX => $adjustmentInclTax,
                    MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_BASE_BUNDLE_ADJUSTMENT => $baseAdjustmentExclTax,
                    MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_BASE_BUNDLE_ADJUSTMENT_INCL_TAX => $baseAdjustmentInclTax,
                ]
            );

            $taxAmount = $adjustmentInclTax - $adjustmentExclTax;
            $baseTaxAmount = $baseAdjustmentInclTax - $baseAdjustmentExclTax;

            $invoice->setTaxAmount($invoice->getTaxAmount() + $taxAmount);
            $invoice->setBaseTaxAmount($invoice->getBaseTaxAmount() + $baseTaxAmount);
            $invoice->setGrandTotal($invoice->getGrandTotal() + $adjustmentInclTax);
            $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseAdjustmentInclTax);
        }

        return $this;
    }
}
