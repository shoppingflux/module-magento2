<?php

namespace ShoppingFeed\Manager\Model\Sales\Creditmemo\Total;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;

class BundleAdjustment extends AbstractTotal
{
    public function collect(Creditmemo $creditmemo)
    {
        parent::collect($creditmemo);

        $order = $creditmemo->getOrder();

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
            $creditmemo->addData(
                [
                    MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_BUNDLE_ADJUSTMENT => $adjustmentExclTax,
                    MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_BUNDLE_ADJUSTMENT_INCL_TAX => $adjustmentInclTax,
                    MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_BASE_BUNDLE_ADJUSTMENT => $baseAdjustmentExclTax,
                    MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_BASE_BUNDLE_ADJUSTMENT_INCL_TAX => $baseAdjustmentInclTax,
                ]
            );

            $taxAmount = $adjustmentInclTax - $adjustmentExclTax;
            $baseTaxAmount = $baseAdjustmentInclTax - $baseAdjustmentExclTax;

            $creditmemo->setTaxAmount($creditmemo->getTaxAmount() + $taxAmount);
            $creditmemo->setBaseTaxAmount($creditmemo->getBaseTaxAmount() + $baseTaxAmount);
            $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $adjustmentInclTax);
            $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseAdjustmentInclTax);
        }

        return $this;
    }
}
