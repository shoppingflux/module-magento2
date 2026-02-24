<?php

namespace ShoppingFeed\Manager\Plugin\Customer\Address\Validator;

use Magento\Customer\Model\Address\AbstractAddress;
use Magento\Customer\Model\Address\Validator\Country as CountryValidator;
use Magento\Framework\App\ObjectManager;
use ShoppingFeed\Manager\Model\Sales\Order\ConfigInterface as OrderConfigInterface;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as OrderImporterInterface;
use ShoppingFeed\Manager\Model\Sales\Order\ImportStateInterface as SalesOrderImportStateInterface;

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
     * @param CountryValidator $subject
     * @param array $result
     * @param AbstractAddress $address
     * @return array
     */
    public function afterValidate(CountryValidator $subject, $result, AbstractAddress $address): array
    {
        if (
            is_array($result)
            && !empty($result)
            && $this->salesOrderImportState->isImportRunning()
            && ($store = $this->salesOrderImportState->getImportRunningForStore())
            && !$this->orderGeneralConfig->shouldCheckAddressCountries($store)
        ) {
            return [];
        }

        return $result;
    }
}
