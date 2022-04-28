<?php

namespace ShoppingFeed\Manager\Block\Payment;

use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\Info as BaseInfo;
use ShoppingFeed\Manager\Payment\Gateway\Config\Config as MarketplacePaymentConfig;

class Info extends BaseInfo
{
    const INFO_KEY_TITLE = 'method_title';

    /**
     * @var MarketplacePaymentConfig
     */
    private $marketplacePaymentConfig;

    /**
     * @param Context $context
     * @param MarketplacePaymentConfig $marketplacePaymentConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        MarketplacePaymentConfig $marketplacePaymentConfig,
        array $data = []
    ) {
        $this->marketplacePaymentConfig = $marketplacePaymentConfig;
        parent::__construct($context, $data);
    }

    protected function _toHtml()
    {
        try {
            $title = trim((string) $this->getInfo()->getAdditionalInformation(static::INFO_KEY_TITLE));
        } catch (\Exception $e) {
            $title = '';
        }

        if ('' !== $title) {
            $this->marketplacePaymentConfig->setForcedValue(MarketplacePaymentConfig::FIELD_NAME_TITLE, $title);
        }

        $output = parent::_toHtml();

        if ('' !== $title) {
            $this->marketplacePaymentConfig->unsetForcedValue(MarketplacePaymentConfig::FIELD_NAME_TITLE);
        }

        return $output;
    }
}
