<?php

namespace ShoppingFeed\Manager\Plugin\Tax\Sales\Total\Quote;

use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote\Address\Total as QuoteAddressTotal;
use Magento\Tax\Api\Data\QuoteDetailsItemInterface;
use Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector;


class CommonTaxCollectorPlugin
{
    /**
     * @param ShippingAssignmentInterface $shippingAssignment
     * @return bool
     */
    private function isShoppingFeedShippingAssignment(ShippingAssignmentInterface $shippingAssignment)
    {
        $extensionAttributes = $shippingAssignment->getShipping()->getAddress()->getExtensionAttributes();
        return (null !== $extensionAttributes) ? $extensionAttributes->getSfmIsShoppingFeedOrder() : false;
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
     * @param $priceIncludesTax
     * @param $useBaseCurrency
     * @return QuoteDetailsItemInterface[]
     */
    public function afterMapItems(
        CommonTaxCollector $subject,
        array $itemDataObjects,
        ShippingAssignmentInterface $shippingAssignment,
        $priceIncludesTax,
        $useBaseCurrency
    ) {
        if ($this->isShoppingFeedShippingAssignment($shippingAssignment)) {
            foreach ($itemDataObjects as $itemDataObject) {
                if ($itemDataObject instanceof QuoteDetailsItemInterface) {
                    $itemDataObject->setIsTaxIncluded(true);
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
     * @param $useBaseCurrency
     * @return mixed
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
            }
        }

        return $shippingDataObject;
    }
}
