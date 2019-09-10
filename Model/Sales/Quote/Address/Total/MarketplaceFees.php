<?php

namespace ShoppingFeed\Manager\Model\Sales\Quote\Address\Total;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as OrderImporterInterface;

class MarketplaceFees extends AbstractTotal
{
    const AMOUNT_KEY_BASE = 'base';
    const AMOUNT_KEY_STORE = 'store';

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var OrderImporterInterface
     */
    private $orderImporter;

    /**
     * @param PriceCurrencyInterface $priceCurrency
     * @param OrderImporterInterface $orderImporter
     */
    public function __construct(PriceCurrencyInterface $priceCurrency, OrderImporterInterface $orderImporter)
    {
        $this->priceCurrency = $priceCurrency;
        $this->orderImporter = $orderImporter;
    }

    /**
     * @param Quote $quote
     * @return array|null
     */
    public function getQuoteMarketplaceFeesAmounts(Quote $quote)
    {
        if ($this->orderImporter->isImportRunning()
            && $this->orderImporter->isCurrentlyImportedQuote($quote)
            && ($marketplaceOrder = $this->orderImporter->getCurrentlyImportedMarketplaceOrder())
        ) {
            $feesAmount = $marketplaceOrder->getFeesAmount();
            $baseFeesAmount = $this->priceCurrency->convert($feesAmount, $quote->getStore());

            return [
                self::AMOUNT_KEY_STORE => $feesAmount,
                self::AMOUNT_KEY_BASE => $baseFeesAmount,
            ];
        }

        return null;
    }

    public function collect(Quote $quote, ShippingAssignmentInterface $shippingAssignment, Total $total)
    {
        parent::collect($quote, $shippingAssignment, $total);

        $feesAmounts = $this->getQuoteMarketplaceFeesAmounts($quote);

        if (is_array($feesAmounts)) {
            $total->setTotalAmount($this->getCode(), $feesAmounts[self::AMOUNT_KEY_STORE]);
            $total->setBaseTotalAmount($this->getCode(), $feesAmounts[self::AMOUNT_KEY_BASE]);
        }

        return $this;
    }
}
