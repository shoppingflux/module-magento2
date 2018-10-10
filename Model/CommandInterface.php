<?php

namespace ShoppingFeed\Manager\Model;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Model\Command\ConfigInterface;

interface CommandInterface
{
    /**
     * @return ConfigInterface
     */
    public function getConfig();

    /**
     * @return string
     */
    public function getLabel();

    /**
     * @param DataObject $configData
     * @throws LocalizedException
     */
    public function run(DataObject $configData);
}
