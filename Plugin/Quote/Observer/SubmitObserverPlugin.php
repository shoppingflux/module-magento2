<?php

namespace ShoppingFeed\Manager\Plugin\Quote\Observer;

use Magento\Framework\Event\Observer;
use Magento\Quote\Observer\SubmitObserver;
use Magento\Sales\Model\Order;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as OrderImporterInterface;

class SubmitObserverPlugin
{
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

    public function aroundExecute(SubmitObserver $subject, callable $proceed, Observer $observer)
    {
        $order = $observer->getEvent()->getData(self::EVENT_KEY_ORDER);

        if (
            ($order instanceof Order)
            && !$this->orderImporter->isCurrentlyImportedSalesOrder($order)
        ) {
            $proceed($observer);
        }
    }
}
