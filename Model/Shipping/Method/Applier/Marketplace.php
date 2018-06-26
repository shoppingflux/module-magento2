<?php

namespace ShoppingFeed\Manager\Model\Shipping\Method\Applier;

use ShoppingFeed\Manager\Model\Shipping\Method\AbstractApplier;
use ShoppingFeed\Manager\Model\Shipping\Method\Applier\Config\MarketplaceInterface as ConfigInterface;


/**
 * @method ConfigInterface getConfig()
 */
class Marketplace extends AbstractApplier
{
    public function __construct(ConfigInterface $config)
    {
        parent::__construct($config);
    }

    public function getLabel()
    {
        return __('Marketplace (Default)');
    }
}
