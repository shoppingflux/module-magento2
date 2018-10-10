<?php

namespace ShoppingFeed\Manager\Model\Shipping\Carrier;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory as RateErrorFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Psr\Log\LoggerInterface;

class Marketplace extends AbstractCarrier implements CarrierInterface
{
    const CARRIER_CODE = 'sfm_marketplace';
    const METHOD_CODE = 'marketplace';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param RateErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        RateErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        array $data = []
    ) {
        $this->_code = self::CARRIER_CODE;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    public function collectRates(RateRequest $request)
    {
        return false;
    }

    public function getAllowedMethods()
    {
        return [ self::METHOD_CODE => __('Marketplace') ];
    }
}
