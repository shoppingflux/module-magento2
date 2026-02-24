<?php

namespace ShoppingFeed\Manager\Plugin\Quote\Item;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\ObjectManager;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use ShoppingFeed\Manager\Model\Sales\Order\ConfigInterface as OrderConfigInterface;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as OrderImporterInterface;
use ShoppingFeed\Manager\Model\Sales\Order\ImportStateInterface as SalesOrderImportStateInterface;

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
     * @var SalesOrderImportStateInterface
     */
    private $salesOrderImportState;

    /**
     * @param OrderConfigInterface $orderGeneralConfig
     * @param OrderImporterInterface $orderImporter
     * @param SalesOrderImportStateInterface|null $salesOrderImportState
     */
    public function __construct(
        OrderConfigInterface $orderGeneralConfig,
        OrderImporterInterface $orderImporter,
        ?SalesOrderImportStateInterface $salesOrderImportState = null
    ) {
        $this->orderGeneralConfig = $orderGeneralConfig;
        $this->orderImporter = $orderImporter;
        $this->salesOrderImportState = $salesOrderImportState
            ?? ObjectManager::getInstance()->get(SalesOrderImportStateInterface::class);
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
            && $this->salesOrderImportState->isCurrentlyImportedQuote($quote)
            && ($store = $this->salesOrderImportState->getImportRunningForStore())
            && !$this->orderGeneralConfig->shouldCheckProductAvailabilityAndOptions($store)
        ) {
            $result->setSkipCheckRequiredOption(true);
        }

        return $result;
    }
}
