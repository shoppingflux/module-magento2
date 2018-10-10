<?php

namespace ShoppingFeed\Manager\Model\Shipping\Method\Applier;

use Magento\Framework\DataObject;
use ShoppingFeed\Manager\Model\Config\Basic\ConfigInterface as BaseConfigInterface;

interface ConfigInterface extends BaseConfigInterface
{
    /**
     * @param DataObject $configData
     * @return bool
     */
    public function shouldOnlyApplyIfAvailable(DataObject $configData);

    /**
     * @param DataObject $configData
     * @return string
     */
    public function getDefaultCarrierTitle(DataObject $configData);

    /**
     * @param DataObject $configData
     * @return bool
     */
    public function shouldForceDefaultCarrierTitle(DataObject $configData);

    /**
     * @param DataObject $configData
     * @return string
     */
    public function getDefaultMethodTitle(DataObject $configData);

    /**
     * @param DataObject $configData
     * @return bool
     */
    public function shouldForceDefaultMethodTitle(DataObject $configData);
}
