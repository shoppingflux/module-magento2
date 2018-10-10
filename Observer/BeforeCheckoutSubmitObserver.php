<?php

namespace ShoppingFeed\Manager\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as OrderImporterInterface;

class BeforeCheckoutSubmitObserver implements ObserverInterface
{
    const EVENT_KEY_QUOTE = 'quote';

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

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (($quote = $observer->getEvent()->getData(static::EVENT_KEY_QUOTE))
            && ($quote instanceof Quote)
            && $this->orderImporter->isCurrentlyImportedQuote($quote)
        ) {
            $this->orderImporter->tagImportedQuote($quote);
            $quote->collectTotals();
        }
    }
}
