<?php

namespace ShoppingFeed\Manager\Model\Shipping\Method;

use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\AddressInterface as MarketplaceAddressInterface;
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
     * @param MarketplaceOrderInterface $marketplaceOrder
     * @param MarketplaceAddressInterface $marketplaceShippingAddress
     * @param QuoteAddress $quoteShippingAddress
     * @param DataObject $configData
     * @return Result|null
     */
    public function applyToQuoteShippingAddress(
        MarketplaceOrderInterface $marketplaceOrder,
        MarketplaceAddressInterface $marketplaceShippingAddress,
        QuoteAddress $quoteShippingAddress,
        DataObject $configData
    );

    /**
     * @param QuoteAddress $quoteShippingAddress
     * @param Result $result
     * @param DataObject $configData
     * @return $this
     */
    public function commitOnQuoteShippingAddress(
        QuoteAddress $quoteShippingAddress,
        Result $result,
        DataObject $configData
    );
}
