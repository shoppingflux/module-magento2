<?php

namespace ShoppingFeed\Manager\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;
use ShoppingFeed\Manager\Model\Sales\Quote\Address\Total\MarketplaceFees as QuoteFeesTotal;

class BeforeQuoteSubmitObserver implements ObserverInterface
{
    const EVENT_KEY_QUOTE = 'quote';
    const EVENT_KEY_ORDER = 'order';

    /**
     * @var QuoteFeesTotal
     */
    private $quoteFeesTotal;

    /**
     * @param QuoteFeesTotal $quoteFeesTotal
     */
    public function __construct(QuoteFeesTotal $quoteFeesTotal)
    {
        $this->quoteFeesTotal = $quoteFeesTotal;
    }

    public function execute(Observer $observer)
    {
        if (
            ($quote = $observer->getEvent()->getData(self::EVENT_KEY_QUOTE))
            && ($quote instanceof Quote)
            && ($order = $observer->getEvent()->getData(self::EVENT_KEY_ORDER))
            && ($order instanceof Order)
        ) {
            if (is_array($feesAmounts = $this->quoteFeesTotal->getQuoteMarketplaceFeesAmounts($quote))) {
                $order->setData(
                    MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_MARKETPLACE_FEES_AMOUNT,
                    $feesAmounts[QuoteFeesTotal::AMOUNT_KEY_STORE] ?? 0
                );

                $order->setData(
                    MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_MARKETPLACE_FEES_BASE_AMOUNT,
                    $feesAmounts[QuoteFeesTotal::AMOUNT_KEY_BASE] ?? 0
                );
            }

            $order->addData(
                array_intersect_key(
                    $quote->getShippingAddress()->getData(),
                    array_flip(
                        [
                            MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_BUNDLE_ADJUSTMENT,
                            MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_BUNDLE_ADJUSTMENT_INCL_TAX,
                            MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_BASE_BUNDLE_ADJUSTMENT,
                            MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_BASE_BUNDLE_ADJUSTMENT_INCL_TAX,
                        ]
                    )
                )
            );
        }
    }
}
