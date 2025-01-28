<?php

namespace ShoppingFeed\Manager\Plugin\Customer\Address\Validator;

use Magento\Customer\Model\Address\AbstractAddress;
use Magento\Customer\Model\Address\Validator\Country as CountryValidator;
use ShoppingFeed\Manager\Model\Sales\Order\ConfigInterface as OrderConfigInterface;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as OrderImporterInterface;

class CountryPlugin
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
     * @param CountryValidator $subject
     * @param array $result
     * @param AbstractAddress $address
     * @return bool
     */
    public function afterValidate(CountryValidator $subject, $result, AbstractAddress $address): array
    {
        if (
            is_array($result)
            && !empty($result)
            && $this->orderImporter->isImportRunning()
            && ($store = $this->orderImporter->getImportRunningForStore())
            && !$this->orderGeneralConfig->shouldCheckAddressCountries($store)
        ) {
            return [];
        }

        return $result;
    }
}