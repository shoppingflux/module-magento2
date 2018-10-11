<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section;

use ShoppingFeed\Manager\Model\Account\Store\ConfigInterface as BaseConfig;
use ShoppingFeed\Manager\Model\Feed\Product\RefreshableConfigInterface;

interface ConfigInterface extends BaseConfig, RefreshableConfigInterface
{
    /**
     * @param AbstractType $type
     * @return $this
     */
    public function setType(AbstractType $type);
}
