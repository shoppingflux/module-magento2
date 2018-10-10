<?php

namespace ShoppingFeed\Manager\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as OrderImporterInterface;

class OnQuoteSubmitSuccessObserver implements ObserverInterface
{
    const EVENT_KEY_QUOTE = 'quote';
    const EVENT_KEY_ORDER = 'order';

    /**
     * @var OrderImporterInterface
     */
    private $orderImporter;

    /**
     * @param OrderImporterInterface $orderImporter
     */
    public function __construct(OrderImporterInterface $orderImporter)
    {
        $this->orderImporter = $orderImporter;
    }

    public function execute(Observer $observer)
    {
        if (($quote = $observer->getEvent()->getData(static::EVENT_KEY_QUOTE))
            && ($quote instanceof Quote)
            && $this->orderImporter->isCurrentlyImportedQuote($quote)
            && ($order = $observer->getEvent()->getData(self::EVENT_KEY_ORDER))
            && ($order instanceof Order)
        ) {
            $this->orderImporter->handleImportedSalesOrder($order);
        }
    }
}
