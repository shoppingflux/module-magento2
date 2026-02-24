<?php

namespace ShoppingFeed\Manager\Plugin\Quote\ValidationRules;

use Magento\Framework\App\ObjectManager;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ValidationRules\AllowedCountryValidationRule;
use ShoppingFeed\Manager\Model\Sales\Order\ConfigInterface as OrderConfigInterface;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as OrderImporterInterface;
use ShoppingFeed\Manager\Model\Sales\Order\ImportStateInterface as SalesOrderImportStateInterface;

class AllowedCountryPlugin
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
     * @param AllowedCountryValidationRule $subject
     * @param array $result
     * @param Quote $quote
     * @return array
     */
    public function afterValidate(AllowedCountryValidationRule $subject, $result, Quote $quote): array
    {
        if (
            is_array($result)
            && !empty($result)
            && $this->salesOrderImportState->isCurrentlyImportedQuote($quote)
            && ($store = $this->salesOrderImportState->getImportRunningForStore())
            && !$this->orderGeneralConfig->shouldCheckAddressCountries($store)
        ) {
            return [];
        }

        return $result;
    }
}
