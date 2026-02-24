<?php

namespace ShoppingFeed\Manager\Plugin\Customer\Validator;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Validator;
use Magento\Framework\App\ObjectManager;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as OrderImporterInterface;
use ShoppingFeed\Manager\Model\Sales\Order\ImportStateInterface as SalesOrderImportStateInterface;

class NamePlugin
{
    /**
     * @var OrderImporterInterface
     */
    private $orderImporter;

    /**
     * @var SalesOrderImportStateInterface
     */
    private $salesOrderImportState;

    /**
     * @param OrderImporterInterface $orderImporter
     * @param SalesOrderImportStateInterface|null $salesOrderImportState
     */
    public function __construct(
        OrderImporterInterface $orderImporter,
        ?SalesOrderImportStateInterface $salesOrderImportState = null
    ) {
        $this->orderImporter = $orderImporter;
        $this->salesOrderImportState = $salesOrderImportState
            ?? ObjectManager::getInstance()->get(SalesOrderImportStateInterface::class);
    }

    /**
     * @param Validator\Name $subject
     * @param callable $proceed
     * @param Customer $customer
     * @return bool
     */
    public function aroundIsValid(Validator\Name $subject, callable $proceed, $customer)
    {
        return (
            $this->salesOrderImportState->isImportRunning()
            || $proceed($customer)
        );
    }
}
