<?php

namespace ShoppingFeed\Manager\Model\Sales\Invoice\Total;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;

class MarketplaceFees extends AbstractTotal
{
    public function collect(Invoice $invoice)
    {
        parent::collect($invoice);

        $order = $invoice->getOrder();
        $amount = $order->getData(MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_MARKETPLACE_FEES_AMOUNT);
        $baseAmount = $order->getData(MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_MARKETPLACE_FEES_BASE_AMOUNT);

        if ((null !== $amount) && (null !== $baseAmount)) {
            $invoice->setData(
                MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_MARKETPLACE_FEES_AMOUNT,
                $amount
            );

            $invoice->setData(
                MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_MARKETPLACE_FEES_BASE_AMOUNT,
                $baseAmount
            );

            $invoice->setGrandTotal($invoice->getGrandTotal() + $amount);
            $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseAmount);
        }

        return $this;
    }
}
