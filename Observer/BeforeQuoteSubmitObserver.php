<?php

namespace ShoppingFeed\Manager\Observer;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Registry;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;
use ShoppingFeed\Manager\Model\Sales\Order\ConfigInterface as OrderConfigInterface;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as OrderImporterInterface;
use ShoppingFeed\Manager\Model\Sales\Quote\Address\Total\MarketplaceFees as QuoteFeesTotal;

class BeforeQuoteSubmitObserver implements ObserverInterface
{
    const EVENT_KEY_QUOTE = 'quote';
    const EVENT_KEY_ORDER = 'order';

    const REGISTRY_KEY_IMPORTED_SALES_ORDER_INCREMENT_ID = 'sfm_imported_sales_order_increment_id';

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var QuoteFeesTotal
     */
    private $quoteFeesTotal;

    /**
     * @var OrderImporterInterface
     */
    private $orderImporter;

    /**
     * @var OrderConfigInterface
     */
    private $orderGeneralConfig;

    /**
     * @param QuoteFeesTotal $quoteFeesTotal
     * @param Registry|null $coreRegistry
     * @param OrderImporterInterface $orderImporter
     * @param OrderConfigInterface|null $orderGeneralConfig
     */
    public function __construct(
        QuoteFeesTotal $quoteFeesTotal,
        Registry $coreRegistry = null,
        OrderImporterInterface $orderImporter = null,
        OrderConfigInterface $orderGeneralConfig = null
    ) {
        $this->quoteFeesTotal = $quoteFeesTotal;

        $this->coreRegistry = $coreRegistry
            ?? ObjectManager::getInstance()->get(Registry::class);

        $this->orderImporter = $orderImporter
            ?? ObjectManager::getInstance()->get(OrderImporterInterface::class);

        $this->orderGeneralConfig = $orderGeneralConfig
            ?? ObjectManager::getInstance()->get(OrderConfigInterface::class);
    }

    public function execute(Observer $observer)
    {
        if (
            ($quote = $observer->getEvent()->getData(self::EVENT_KEY_QUOTE))
            && ($quote instanceof Quote)
            && ($order = $observer->getEvent()->getData(self::EVENT_KEY_ORDER))
            && ($order instanceof Order)
        ) {
            if ($this->orderImporter->isCurrentlyImportedQuote($quote)) {
                $store = $this->orderImporter->getImportRunningForStore();
                $marketplaceName = null;

                if ($marketplaceOrder = $this->orderImporter->getCurrentlyImportedMarketplaceOrder()) {
                    $marketplaceName = $marketplaceOrder->getMarketplaceName();
                }

                if (
                    $marketplaceName
                    && !$this->orderGeneralConfig->shouldSendOrderEmailForMarketplace($store, $marketplaceName)
                    && !$this->orderGeneralConfig->shouldSendInvoiceEmailForMarketplace($store, $marketplaceName)
                ) {
                    $order->setCanSendNewEmailFlag(false);
                }
            }

            if ($salesOrderIncrementId = $order->getIncrementId()) {
                if ($this->coreRegistry->registry(self::REGISTRY_KEY_IMPORTED_SALES_ORDER_INCREMENT_ID)) {
                    $this->coreRegistry->unregister(self::REGISTRY_KEY_IMPORTED_SALES_ORDER_INCREMENT_ID);
                }

                $this->coreRegistry->register(
                    self::REGISTRY_KEY_IMPORTED_SALES_ORDER_INCREMENT_ID,
                    $salesOrderIncrementId
                );
            }

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
