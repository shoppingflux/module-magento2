<?php

namespace ShoppingFeed\Manager\Model\Shipping\Method;

use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use ShoppingFeed\Manager\Model\Shipping\Method\Applier\ConfigInterface;
use ShoppingFeed\Manager\Model\Shipping\Method\Applier\Result;

interface ApplierInterface
{
    /**
     * @return ConfigInterface
     */
    public function getConfig();

    /**
     * @return string
     */
    public function getLabel();

    /**
     * @param QuoteAddress $shippingAddress
     * @param float $orderShippingAmount
     * @param DataObject $configData
     * @return Result|null
     */
    public function applyToQuoteShippingAddress(
        QuoteAddress $shippingAddress,
        $orderShippingAmount,
        DataObject $configData
    );
}
