<?php

namespace ShoppingFeed\Manager\Model\Shipping\Method;

use ShoppingFeed\Manager\Model\Shipping\Method\Applier\ConfigInterface;


interface ApplierInterface
{
    /**
     * @return ConfigInterface
     */
    public function getConfig();

    /**
     * @return string
     */
    public function getLabel();
}
