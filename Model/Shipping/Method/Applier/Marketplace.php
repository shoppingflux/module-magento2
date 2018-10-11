<?php

namespace ShoppingFeed\Manager\Model\Shipping\Method\Applier;

use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use ShoppingFeed\Manager\Model\Shipping\Carrier\Marketplace as MarketplaceCarrier;
use ShoppingFeed\Manager\Model\Shipping\Method\AbstractApplier;
use ShoppingFeed\Manager\Model\Shipping\Method\Applier\Config\MarketplaceInterface as ConfigInterface;

/**
 * @method ConfigInterface getConfig()
 */
class Marketplace extends AbstractApplier
{
    public function __construct(ConfigInterface $config, ResultFactory $resultFactory)
    {
        parent::__construct($config, $resultFactory);
    }

    public function getLabel()
    {
        return __('Marketplace (Default)');
    }

    public function applyToQuoteShippingAddress(
        QuoteAddress $shippingAddress,
        $orderShippingAmount,
        DataObject $configData
    ) {
        return $this->resultFactory->create(
            [
                'carrierCode' => MarketplaceCarrier::CARRIER_CODE,
                'methodCode' => MarketplaceCarrier::METHOD_CODE,
                'carrierTitle' => $this->getConfig()->getDefaultCarrierTitle($configData),
                'methodTitle' => $this->getConfig()->getDefaultMethodTitle($configData),
                'cost' => $orderShippingAmount,
                'price' => $orderShippingAmount,
            ]
        );
    }
}
