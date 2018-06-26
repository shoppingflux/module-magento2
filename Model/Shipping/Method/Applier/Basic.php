<?php

namespace ShoppingFeed\Manager\Model\Shipping\Method\Applier;

use ShoppingFeed\Manager\Model\Shipping\Method\AbstractApplier;
use ShoppingFeed\Manager\Model\Shipping\Method\Applier\Config\BasicInterface as ConfigInterface;


/**
 * @method ConfigInterface getConfig()
 */
class Basic extends AbstractApplier
{
    public function __construct(ConfigInterface $config)
    {
        parent::__construct($config);
    }

    public function getLabel()
    {
        return __('Basic');
    }
}
