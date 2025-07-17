<?php

namespace ShoppingFeed\Manager\Observer;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender as InvoiceEmailSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender as OrderEmailSender;
use ShoppingFeed\Manager\Model\Feed\RealTimeUpdater as RealTimeFeedUpdater;
use ShoppingFeed\Manager\Model\Sales\Order\ConfigInterface as OrderConfigInterface;
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
     * @var OrderConfigInterface
     */
    private $orderGeneralConfig;

    /**
     * @var OrderEmailSender
     */
    private $orderEmailSender;

    /**
     * @var InvoiceEmailSender
     */
    private $invoiceEmailSender;

    /**
     * @param RealTimeFeedUpdater $realTimeFeedUpdater
     * @param OrderImporterInterface $orderImporter
     * @param OrderConfigInterface|null $orderGeneralConfig
     * @param OrderEmailSender|null $orderEmailSender
     * @param InvoiceEmailSender|null $invoiceEmailSender
     */
    public function __construct(
        RealTimeFeedUpdater $realTimeFeedUpdater,
        OrderImporterInterface $orderImporter,
        ?OrderConfigInterface $orderGeneralConfig = null,
        ?OrderEmailSender $orderEmailSender = null,
        ?InvoiceEmailSender $invoiceEmailSender = null
    ) {
        $this->realTimeFeedUpdater = $realTimeFeedUpdater;
        $this->orderImporter = $orderImporter;
        $this->orderGeneralConfig = $orderGeneralConfig
            ?? ObjectManager::getInstance()->get(OrderConfigInterface::class);
        $this->orderEmailSender = $orderEmailSender
            ?? ObjectManager::getInstance()->get(OrderEmailSender::class);
        $this->invoiceEmailSender = $invoiceEmailSender
            ?? ObjectManager::getInstance()->get(InvoiceEmailSender::class);
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

            if (
                ($quote = $observer->getEvent()->getData(static::EVENT_KEY_QUOTE))
                && ($quote instanceof Quote)
                && $this->orderImporter->isCurrentlyImportedQuote($quote)
            ) {
                $this->orderImporter->handleImportedSalesOrder($order);

                $store = $this->orderImporter->getImportRunningForStore();
                $marketplaceName = '';
                $marketplaceOrder = $this->orderImporter->getCurrentlyImportedMarketplaceOrder();

                if ($marketplaceOrder) {
                    $marketplaceName = $marketplaceOrder->getMarketplaceName();
                }

                if (
                    (null !== $store)
                    && ('' !== $marketplaceName)
                    && $order->getCanSendNewEmailFlag()
                ) {
                    try {
                        if (
                            !$order->getEmailSent()
                            && $this->orderGeneralConfig->shouldSendOrderEmailForMarketplace($store, $marketplaceName)
                        ) {
                            $this->orderEmailSender->send($order);
                        }

                        $invoice = current($order->getInvoiceCollection()->getItems());

                        if (
                            $invoice
                            && !$invoice->getEmailSent()
                            && $this->orderGeneralConfig->shouldSendInvoiceEmailForMarketplace($store, $marketplaceName)
                        ) {
                            $this->invoiceEmailSender->send($invoice);
                        }
                    } catch (\Exception $e) {
                        // Do not prevent the order from being completed.
                    }
                }
            }
        }
    }
}
