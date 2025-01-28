<?php

namespace ShoppingFeed\Manager\Plugin\Quote\ValidationRules;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ValidationRules\AllowedCountryValidationRule;
use ShoppingFeed\Manager\Model\Sales\Order\ConfigInterface as OrderConfigInterface;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as OrderImporterInterface;

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
     * @param OrderConfigInterface $orderGeneralConfig
     * @param OrderImporterInterface $orderImporter
     */
    public function __construct(OrderConfigInterface $orderGeneralConfig, OrderImporterInterface $orderImporter)
    {
        $this->orderGeneralConfig = $orderGeneralConfig;
        $this->orderImporter = $orderImporter;
    }

    /**
     * @param AllowedCountryValidationRule $subject
     * @param array $result
     * @param Quote $quote
     * @return bool
     */
    public function afterValidate(AllowedCountryValidationRule $subject, $result, Quote $quote): array
    {
        if (
            is_array($result)
            && !empty($result)
            && $this->orderImporter->isCurrentlyImportedQuote($quote)
            && ($store = $this->orderImporter->getImportRunningForStore())
            && !$this->orderGeneralConfig->shouldCheckAddressCountries($store)
        ) {
            return [];
        }

        return $result;
    }
}