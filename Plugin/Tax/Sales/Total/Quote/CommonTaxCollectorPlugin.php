<?php

namespace ShoppingFeed\Manager\Plugin\Tax\Sales\Total\Quote;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\AddressExtensionInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote\Address\Total as QuoteAddressTotal;
use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector;
use ShoppingFeed\Manager\Model\Sales\Order\Business\TaxManager as BusinessTaxManager;

class CommonTaxCollectorPlugin
{
    /**
     * @var BusinessTaxManager
     */
    private $businessTaxManager;

    /**
     * @param BusinessTaxManager $businessTaxManager
     */
    public function __construct(BusinessTaxManager $businessTaxManager)
    {
        $this->businessTaxManager = $businessTaxManager;
    }

    /**
     * @param ShippingAssignmentInterface $shippingAssignment
     * @return AddressExtensionInterface|null
     */
    private function getShippingAssignmentAttributes(ShippingAssignmentInterface $shippingAssignment)
    {
        return $shippingAssignment->getShipping()
            ->getAddress()
            ->getExtensionAttributes();
    }

    /**
     * @param ShippingAssignmentInterface $shippingAssignment
     * @return bool
     */
    private function isShoppingFeedShippingAssignment(ShippingAssignmentInterface $shippingAssignment)
    {
        $attributes = $this->getShippingAssignmentAttributes($shippingAssignment);
        return (null !== $attributes) ? $attributes->getSfmIsShoppingFeedOrder() : false;
    }

    /**
     * @param ShippingAssignmentInterface $shippingAssignment
     * @return bool
     */
    private function isShoppingFeedBusinessShippingAssignment(ShippingAssignmentInterface $shippingAssignment)
    {
        $attributes = $this->getShippingAssignmentAttributes($shippingAssignment);
        return (null !== $attributes) ? $attributes->getSfmIsShoppingFeedBusinessOrder() : false;
    }

    /**
     * @param CommonTaxCollector $subject
     * @param callable $proceed
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param bool $priceIncludesTax
     * @param bool $useBaseCurrency
     * @return QuoteDetailsItemInterface[]
     * @throws InputException
     * @throws LocalizedException
     */
    public function aroundMapItems(
        CommonTaxCollector $subject,
        callable $proceed,
        ShippingAssignmentInterface $shippingAssignment,
        $priceIncludesTax,
        $useBaseCurrency
    ) {
        if ($this->isShoppingFeedShippingAssignment($shippingAssignment)) {
            $priceIncludesTax = true;
        }

        /** @var QuoteDetailsItemInterface[] $itemDataObjects */
        $itemDataObjects = $proceed($shippingAssignment, $priceIncludesTax, $useBaseCurrency);

        if ($this->isShoppingFeedShippingAssignment($shippingAssignment)) {
            $isBusinessAssignment = $this->isShoppingFeedBusinessShippingAssignment($shippingAssignment);

            if ($isBusinessAssignment) {
                $businessTaxClassId = $this->businessTaxManager->getProductTaxClass()->getClassId();
            } else {
                $businessTaxClassId = null;
            }

            foreach ($itemDataObjects as $itemDataObject) {
                if ($itemDataObject instanceof QuoteDetailsItemInterface) {
                    $itemDataObject->setIsTaxIncluded(true);

                    if ($isBusinessAssignment) {
                        $itemDataObject->setTaxClassId($businessTaxClassId);
                        $itemDataObject->getTaxClassKey()->setValue($businessTaxClassId);
                    }
                }
            }
        }

        return $itemDataObjects;
    }

    /**
     * @param CommonTaxCollector $subject
     * @param callable $proceed
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param QuoteAddressTotal $total
     * @param bool $useBaseCurrency
     * @return mixed
     * @throws InputException
     * @throws LocalizedException
     */
    public function aroundGetShippingDataObject(
        CommonTaxCollector $subject,
        callable $proceed,
        ShippingAssignmentInterface $shippingAssignment,
        QuoteAddressTotal $total,
        $useBaseCurrency
    ) {
        /** @var QuoteDetailsItemInterface $shippingDataObject */
        $shippingDataObject = $proceed($shippingAssignment, $total, $useBaseCurrency);

        if ($this->isShoppingFeedShippingAssignment($shippingAssignment)) {
            if ($shippingDataObject instanceof QuoteDetailsItemInterface) {
                $shippingDataObject->setIsTaxIncluded(true);

                if ($this->isShoppingFeedBusinessShippingAssignment($shippingAssignment)) {
                    $businessTaxClassId = $this->businessTaxManager->getProductTaxClass()->getClassId();
                    $shippingDataObject->setTaxClassId($businessTaxClassId);
                    $shippingDataObject->getTaxClassKey()->setValue($businessTaxClassId);
                }
            }
        }

        return $shippingDataObject;
    }
}
