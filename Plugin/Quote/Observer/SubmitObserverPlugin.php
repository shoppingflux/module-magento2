<?php

namespace ShoppingFeed\Manager\Plugin\Quote\Observer;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Quote\Observer\SubmitObserver;
use Magento\Sales\Model\Order;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as OrderImporterInterface;
use ShoppingFeed\Manager\Model\Sales\Order\ImportStateInterface as SalesOrderImportStateInterface;

class SubmitObserverPlugin
{
    const EVENT_KEY_ORDER = 'order';

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

    public function aroundExecute(SubmitObserver $subject, callable $proceed, Observer $observer)
    {
        $order = $observer->getEvent()->getData(self::EVENT_KEY_ORDER);

        if (
            ($order instanceof Order)
            && !$this->salesOrderImportState->isCurrentlyImportedSalesOrder($order)
        ) {
            $proceed($observer);
        }
    }
}
