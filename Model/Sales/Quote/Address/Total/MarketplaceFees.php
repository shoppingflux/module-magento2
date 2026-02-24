<?php

namespace ShoppingFeed\Manager\Model\Sales\Quote\Address\Total;

use Magento\Framework\App\ObjectManager;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as OrderImporterInterface;
use ShoppingFeed\Manager\Model\Sales\Order\ImportStateInterface as SalesOrderImportStateInterface;

class MarketplaceFees extends AbstractTotal
{
    const AMOUNT_KEY_BASE = 'base';
    const AMOUNT_KEY_STORE = 'store';

    /**
     * @var OrderImporterInterface
     */
    private $orderImporter;

    /**
     * @var SalesOrderImportStateInterface
     */
    private $salesOrderImportState;

    /**
     * @param OrderImporterInterface $orderImporter
     * @param SalesOrderImportStateInterface|null $salesOrderImportState
     */
    public function __construct(
        OrderImporterInterface $orderImporter,
        ?SalesOrderImportStateInterface $salesOrderImportState = null
    ) {
        $this->orderImporter = $orderImporter;
        $this->salesOrderImportState = $salesOrderImportState
            ?? ObjectManager::getInstance()->get(SalesOrderImportStateInterface::class);
    }

    /**
     * @param Quote $quote
     * @return array|null
     */
    public function getQuoteMarketplaceFeesAmounts(Quote $quote)
    {
        if (
            $this->salesOrderImportState->isImportRunning()
            && $this->salesOrderImportState->isCurrentlyImportedQuote($quote)
            && ($marketplaceOrder = $this->salesOrderImportState->getCurrentlyImportedMarketplaceOrder())
        ) {
            $store = $quote->getStore();
            $feesAmount = $marketplaceOrder->getFeesAmount();
            $baseFeesAmount = $feesAmount;

            if ($store->getCurrentCurrencyCode() !== $store->getBaseCurrencyCode()) {
                $baseFeesAmount /= $store->getCurrentCurrencyRate();
            }

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
