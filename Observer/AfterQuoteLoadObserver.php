<?php

namespace ShoppingFeed\Manager\Observer;

use Magento\Catalog\Helper\Product as CatalogProductHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use ShoppingFeed\Manager\Model\Sales\Order\ConfigInterface as OrderConfigInterface;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as OrderImporterInterface;

class AfterQuoteLoadObserver implements ObserverInterface
{
    const EVENT_KEY_QUOTE = 'quote';

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
        if (($quote = $observer->getEvent()->getData(static::EVENT_KEY_QUOTE))
            && ($quote instanceof Quote)
            && $this->orderImporter->isCurrentlyImportedQuote($quote)
        ) {
            $this->orderImporter->tagImportedQuote($quote);

            if (($store = $this->orderImporter->getImportRunningForStore())
                && !$this->orderGeneralConfig->shouldCheckProductAvailabilityAndOptions($store)
            ) {
                $quote->setIsSuperMode(true);
                $this->catalogProductHelper->setSkipSaleableCheck(true);
            }

            $quote->setIgnoreOldQty(true);
            $quote->collectTotals();
        }
    }
}
