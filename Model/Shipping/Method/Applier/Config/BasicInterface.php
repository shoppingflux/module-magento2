<?php

namespace ShoppingFeed\Manager\Model\Shipping\Method\Applier\Config;

use Magento\Framework\DataObject;
use ShoppingFeed\Manager\Model\Shipping\Method\Applier\ConfigInterface;


interface BasicInterface extends ConfigInterface
{
    /**
     * @param DataObject $configData
     * @return string
     */
    public function getShippingCarrierCode(DataObject $configData);

    /**
     * @param DataObject $configData
     * @return string
     */
    public function getShippingMethodCode(DataObject $configData);
}
