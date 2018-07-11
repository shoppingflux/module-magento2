<?php

namespace ShoppingFeed\Manager\Model\Shipping\Method;

use ShoppingFeed\Manager\Model\Shipping\Method\Applier\ConfigInterface;
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
}
