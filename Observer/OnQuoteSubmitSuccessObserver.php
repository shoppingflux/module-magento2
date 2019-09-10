<?php

namespace ShoppingFeed\Manager\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use ShoppingFeed\Manager\Model\Feed\RealTimeUpdater as RealTimeFeedUpdater;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as OrderImporterInterface;

class OnQuoteSubmitSuccessObserver implements ObserverInterface
{
    const EVENT_KEY_QUOTE = 'quote';
    const EVENT_KEY_ORDER = 'order';

    /**
     * @var RealTimeFeedUpdater
     */
    private $realTimeFeedUpdater;

    /**
     * @var OrderImporterInterface
     */
    private $orderImporter;

    /**
     * @param RealTimeFeedUpdater $realTimeFeedUpdater
     * @param OrderImporterInterface $orderImporter
     */
    public function __construct(RealTimeFeedUpdater $realTimeFeedUpdater, OrderImporterInterface $orderImporter)
    {
        $this->realTimeFeedUpdater = $realTimeFeedUpdater;
        $this->orderImporter = $orderImporter;
    }

    public function execute(Observer $observer)
    {
        if (($order = $observer->getEvent()->getData(self::EVENT_KEY_ORDER)) && ($order instanceof Order)) {
            $productIds = [];

            foreach ($order->getAllItems() as $orderItem) {
                $productIds[] = (int) $orderItem->getProductId();
            }

            try {
                $this->realTimeFeedUpdater->handleProductsQuantityChange($productIds);
            } catch (\Exception $e) {
                // Do not prevent the order from being completed.
            }

            if (($quote = $observer->getEvent()->getData(static::EVENT_KEY_QUOTE))
                && ($quote instanceof Quote)
                && $this->orderImporter->isCurrentlyImportedQuote($quote)
            ) {
                $this->orderImporter->handleImportedSalesOrder($order);
            }
        }
    }
}
