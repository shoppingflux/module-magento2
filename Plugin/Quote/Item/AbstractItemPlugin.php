<?php

namespace ShoppingFeed\Manager\Plugin\Quote\Item;

use Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use ShoppingFeed\Manager\Model\Sales\Order\ConfigInterface as OrderConfigInterface;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as OrderImporterInterface;

class AbstractItemPlugin
{
    /**
     * @var OrderConfigInterface
     */
    private $orderGeneralConfig;

    /**
     * @var OrderImporterInterface
     */
    private $orderImporter;

    /**
     * @param OrderConfigInterface $orderGeneralConfig
     * @param OrderImporterInterface $orderImporter
     */
    public function __construct(OrderConfigInterface $orderGeneralConfig, OrderImporterInterface $orderImporter)
    {
        $this->orderGeneralConfig = $orderGeneralConfig;
        $this->orderImporter = $orderImporter;
    }

    /**
     * @param AbstractItem $subject
     * @param Product $result
     * @return Product
     */
    public function afterGetProduct(AbstractItem $subject, $result)
    {
        if (
            ($result instanceof Product)
            && ($quote = $subject->getQuote())
            && $this->orderImporter->isCurrentlyImportedQuote($quote)
            && ($store = $this->orderImporter->getImportRunningForStore())
            && !$this->orderGeneralConfig->shouldCheckProductAvailabilityAndOptions($store)
        ) {
            $result->setSkipCheckRequiredOption(true);
        }

        return $result;
    }
}