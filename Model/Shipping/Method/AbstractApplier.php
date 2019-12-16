<?php

namespace ShoppingFeed\Manager\Model\Shipping\Method;

use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Quote\Model\Quote\Address\Rate as QuoteShippingRate;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;
use ShoppingFeed\Manager\Model\Shipping\Method\Applier\ConfigInterface;
use ShoppingFeed\Manager\Model\Shipping\Method\Applier\Result;
use ShoppingFeed\Manager\Model\Shipping\Method\Applier\ResultFactory;

abstract class AbstractApplier implements ApplierInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * @param ConfigInterface $config
     * @param ResultFactory $resultFactory
     */
    public function __construct(ConfigInterface $config, ResultFactory $resultFactory)
    {
        $this->config = $config;
        $this->resultFactory = $resultFactory;
    }

    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param QuoteAddress $quoteShippingAddress
     * @return string[]
     */
    protected function getAvailableShippingMethodCodes(QuoteAddress $quoteShippingAddress)
    {
        $quoteShippingRates = $quoteShippingAddress->getAllShippingRates();
        $shippingMethodCodes = [];

        /** @var QuoteShippingRate $quoteShippingRate */
        foreach ($quoteShippingRates as $quoteShippingRate) {
            $shippingMethodCodes[] = $quoteShippingRate->getCode();
        }

        return $shippingMethodCodes;
    }

    /**
     * @param string $code
     * @param QuoteAddress $quoteShippingAddress
     * @return bool
     */
    protected function isAvailableShippingMethod($code, QuoteAddress $quoteShippingAddress)
    {
        return in_array($code, $this->getAvailableShippingMethodCodes($quoteShippingAddress), true);
    }

    /**
     * @param string $carrierCode
     * @param string $methodCode
     * @param MarketplaceOrderInterface $marketplaceOrder
     * @param QuoteAddress $quoteShippingAddress
     * @param DataObject $configData
     * @return Result|null
     */
    protected function applyCarrierMethodToQuoteShippingAddress(
        $carrierCode,
        $methodCode,
        MarketplaceOrderInterface $marketplaceOrder,
        QuoteAddress $quoteShippingAddress,
        DataObject $configData
    ) {
        $fullCode = $carrierCode . '_' . $methodCode;
        $quoteShippingRates = $quoteShippingAddress->getAllShippingRates();
        $availableShippingRate = null;

        /** @var QuoteShippingRate $quoteShippingRate */
        foreach ($quoteShippingRates as $quoteShippingRate) {
            if ($quoteShippingRate->getCode() === $fullCode) {
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

        $carrierTitle = ($carrierTitle ?? '') ?: $this->getConfig()->getDefaultCarrierTitle($configData);
        $methodTitle = ($methodTitle ?? '') ?: $this->getConfig()->getDefaultMethodTitle($configData);

        return $this->resultFactory->create(
            [
                'carrierCode' => $carrierCode,
                'methodCode' => $methodCode,
                'carrierTitle' => $carrierTitle,
                'methodTitle' => $methodTitle,
                'cost' => $marketplaceOrder->getShippingAmount(),
                'price' => $marketplaceOrder->getShippingAmount(),
            ]
        );
    }

    public function commitOnQuoteShippingAddress(
        QuoteAddress $quoteShippingAddress,
        Result $result,
        DataObject $configData
    ) {
        return $this;
    }
}
