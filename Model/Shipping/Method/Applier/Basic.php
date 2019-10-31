<?php

namespace ShoppingFeed\Manager\Model\Shipping\Method\Applier;

use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\AddressInterface as MarketplaceAddressInterface;
use ShoppingFeed\Manager\Model\Shipping\Method\AbstractApplier;
use ShoppingFeed\Manager\Model\Shipping\Method\Applier\Config\BasicInterface as ConfigInterface;

/**
 * @method ConfigInterface getConfig()
 */
class Basic extends AbstractApplier
{
    public function __construct(ConfigInterface $config, ResultFactory $resultFactory)
    {
        parent::__construct($config, $resultFactory);
    }

    public function getLabel()
    {
        return __('Basic');
    }

    public function applyToQuoteShippingAddress(
        MarketplaceOrderInterface $marketplaceOrder,
        MarketplaceAddressInterface $marketplaceShippingAddress,
        QuoteAddress $quoteShippingAddress,
        DataObject $configData
    ) {
        return $this->applyCarrierMethodToQuoteShippingAddress(
            $this->getConfig()->getShippingCarrierCode($configData),
            $this->getConfig()->getShippingMethodCode($configData),
            $marketplaceOrder,
            $quoteShippingAddress,
            $configData
        );
    }
}
