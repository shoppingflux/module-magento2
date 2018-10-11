<?php

namespace ShoppingFeed\Manager\Model\Shipping\Method\Applier;

use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Quote\Model\Quote\Address\Rate as QuoteShippingRate;
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
        QuoteAddress $shippingAddress,
        $orderShippingAmount,
        DataObject $configData
    ) {
        $carrierCode = $this->getConfig()->getShippingCarrierCode($configData);
        $methodCode = $this->getConfig()->getShippingMethodCode($configData);
        $quoteShippingRates = $shippingAddress->getAllShippingRates();
        $availableShippingRate = null;

        /** @var QuoteShippingRate $quoteShippingRate */
        foreach ($quoteShippingRates as $quoteShippingRate) {
            if ($quoteShippingRate->getCode() === $carrierCode . '_' . $methodCode) {
                $availableShippingRate = $quoteShippingRate;
                break;
            }
        }

        if (null !== $availableShippingRate) {
            if (!$this->getConfig()->shouldForceDefaultCarrierTitle($configData)) {
                $carrierTitle = trim($availableShippingRate->getCarrierTitle());
            }

            if (!$this->getConfig()->shouldForceDefaultMethodTitle($configData)) {
                $methodTitle = trim($availableShippingRate->getMethodTitle());
            }
        } elseif ($this->getConfig()->shouldOnlyApplyIfAvailable($configData)) {
            return null;
        }

        $carrierTitle = '' !== ($carrierTitle ?? '') ?: $this->getConfig()->getDefaultCarrierTitle($configData);
        $methodTitle = '' !== ($methodTitle ?? '') ?: $this->getConfig()->getDefaultMethodTitle($configData);

        return $this->resultFactory->create(
            [
                'carrierCode' => $carrierCode,
                'methodCode' => $methodCode,
                'carrierTitle' => $carrierTitle,
                'methodTitle' => $methodTitle,
                'cost' => $orderShippingAmount,
                'price' => $orderShippingAmount,
            ]
        );
    }
}
