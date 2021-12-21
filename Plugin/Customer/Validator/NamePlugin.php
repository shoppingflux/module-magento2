<?php

namespace ShoppingFeed\Manager\Plugin\Customer\Validator;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Validator;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as OrderImporterInterface;

class NamePlugin
{
    /**
     * @var OrderImporterInterface
     */
    private $orderImporter;

    /**
     * @param OrderImporterInterface $orderImporter
     */
    public function __construct(OrderImporterInterface $orderImporter)
    {
        $this->orderImporter = $orderImporter;
    }

    /**
     * @param Validator\Name $subject
     * @param callable $proceed
     * @param Customer $customer
     * @return bool
     */
    public function aroundIsValid(Validator\Name $subject, callable $proceed, $customer)
    {
        return $this->orderImporter->isImportRunning() || $proceed($customer);
    }
}
