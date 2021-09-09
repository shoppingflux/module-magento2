<?php

namespace ShoppingFeed\Manager\Observer;

use Magento\Catalog\Helper\Product as CatalogProductHelper;
use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Catalog\Model\Product\Attribute\Source\Status as CatalogProductStatus;
use Magento\Catalog\Model\ResourceModel\Product\Collection as CatalogProductCollection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use ShoppingFeed\Manager\Model\Sales\Order\ConfigInterface as OrderConfigInterface;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as OrderImporterInterface;

class AfterQuoteLoadObserver implements ObserverInterface
{
    const EVENT_KEY_QUOTE = 'quote';
    const EVENT_KEY_PRODUCT_COLLECTION = 'collection';

    /**
     * @var CatalogProductHelper
     */
    private $catalogProductHelper;

    /**
     * @var OrderConfigInterface
     */
    private $orderGeneralConfig;

    /**
     * @var OrderImporterInterface
     */
    private $orderImporter;

    /**
     * @var bool
     */
    private $isImportedQuoteEvent = false;

    /**
     * @param CatalogProductHelper $catalogProductHelper
     * @param OrderConfigInterface $orderGeneralConfig
     * @param OrderImporterInterface $orderImporter
     */
    public function __construct(
        CatalogProductHelper $catalogProductHelper,
        OrderConfigInterface $orderGeneralConfig,
        OrderImporterInterface $orderImporter
    ) {
        $this->catalogProductHelper = $catalogProductHelper;
        $this->orderGeneralConfig = $orderGeneralConfig;
        $this->orderImporter = $orderImporter;
    }

    public function execute(Observer $observer)
    {
        if (($quote = $observer->getEvent()->getData(static::EVENT_KEY_QUOTE)) && ($quote instanceof Quote)) {
            $this->isImportedQuoteEvent = $this->orderImporter->isCurrentlyImportedQuote($quote);

            if ($this->isImportedQuoteEvent) {
                $this->orderImporter->tagImportedQuote($quote);

                if (
                    ($store = $this->orderImporter->getImportRunningForStore())
                    && !$this->orderGeneralConfig->shouldCheckProductAvailabilityAndOptions($store)
                ) {
                    $quote->setIsSuperMode(true);
                    $this->catalogProductHelper->setSkipSaleableCheck(true);
                }

                if (
                    ($marketplaceOrder = $this->orderImporter->getCurrentlyImportedMarketplaceOrder())
                    && $marketplaceOrder->isFulfilled()
                ) {
                    $quote->setInventoryProcessed(true);
                }

                $quote->setIgnoreOldQty(true);
                $quote->collectTotals();
            }
        } elseif (
            $this->isImportedQuoteEvent
            && ($productCollection = $observer->getEvent()->getData(static::EVENT_KEY_PRODUCT_COLLECTION))
            && ($productCollection instanceof CatalogProductCollection)
            && ($store = $this->orderImporter->getImportRunningForStore())
            && !$this->orderGeneralConfig->shouldCheckProductAvailabilityAndOptions($store)
        ) {
            /** @var CatalogProduct $product */
            foreach ($productCollection as $product) {
                if ((int) $product->getStatus() === CatalogProductStatus::STATUS_DISABLED) {
                    // Temporarily mark disabled products as enabled to work around any status validation.
                    $product->setStatus(CatalogProductStatus::STATUS_ENABLED);
                }
            }
        }
    }
}
