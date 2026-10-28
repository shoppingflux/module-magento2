<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section;

use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product\RefreshableConfig;

abstract class AbstractConfig extends RefreshableConfig implements ConfigInterface
{
    const SUB_SCOPE = 'section';

    /**
     * @var AbstractType|null
     */
    private $type = null;

    public function getSectionType()
    {
        return $this->type->getCode();
    }

    public function getScopeSubPath()
    {
        return [ self::SUB_SCOPE, $this->getSectionType() ];
    }

    /**
     * @param AbstractType $type
     * @return $this
     */
    public function setType(AbstractType $type)
    {
        $this->type = $type;
        return $this;
    }

    public function isRefreshNeededForGeneralConfigChange(StoreInterface $store)
    {
        // Assume by default that the section data may depend on the general configuration (e.g. URLs).
        return true;
    }
}
