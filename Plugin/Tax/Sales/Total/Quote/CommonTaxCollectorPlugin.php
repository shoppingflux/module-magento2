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
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param bool $priceIncludesTax
     * @param bool $useBaseCurrency
     * @return array|null
     */
    public function beforeMapItems(
        CommonTaxCollector $subject,
        ShippingAssignmentInterface $shippingAssignment,
        $priceIncludesTax,
        $useBaseCurrency
    ) {
        return !$priceIncludesTax && $this->isShoppingFeedShippingAssignment($shippingAssignment)
            ? [ $shippingAssignment, true, $useBaseCurrency ]
            : null;
    }

    /**
     * @param CommonTaxCollector $subject
     * @param QuoteDetailsItemInterface[] $itemDataObjects
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param bool $priceIncludesTax
     * @param bool $useBaseCurrency
     * @return QuoteDetailsItemInterface[]
     * @throws InputException
     * @throws LocalizedException
     */
    public function afterMapItems(
        CommonTaxCollector $subject,
        array $itemDataObjects,
        ShippingAssignmentInterface $shippingAssignment,
        $priceIncludesTax,
        $useBaseCurrency
    ) {
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
     * @param QuoteDetailsItemInterface $shippingDataObject
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param QuoteAddressTotal $total
     * @param bool $useBaseCurrency
     * @return mixed
     * @throws InputException
     * @throws LocalizedException
     */
    public function afterGetShippingDataObject(
        CommonTaxCollector $subject,
        $shippingDataObject,
        ShippingAssignmentInterface $shippingAssignment,
        QuoteAddressTotal $total,
        $useBaseCurrency
    ) {
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
