<?php

namespace ShoppingFeed\Manager\Plugin\Tax;

use Magento\Store\Model\Store;
use Magento\Tax\Model\Config as TaxConfig;

class ConfigPlugin
{
    /**
     * @var bool
     */
    private $isCrossBorderTradeForced = false;

    public function enableForcedCrossBorderTrade()
    {
        $this->isCrossBorderTradeForced = true;
    }

    public function disableForcedCrossBorderTrade()
    {
        $this->isCrossBorderTradeForced = false;
    }

    /**
     * @param TaxConfig $subject
     * @param callable $proceed
     * @param int|string|Store|null $store
     * @return bool
     */
    public function aroundCrossBorderTradeEnabled(TaxConfig $subject, callable $proceed, $store = null)
    {
        return $this->isCrossBorderTradeForced ? true : $proceed($store);
    }
}
