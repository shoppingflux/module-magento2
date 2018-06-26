<?php

namespace ShoppingFeed\Manager\Model\Shipping\Method;

use ShoppingFeed\Manager\Model\Shipping\Method\Applier\ConfigInterface;


abstract class AbstractApplier implements ApplierInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    public function getConfig()
    {
        return $this->config;
    }
}
