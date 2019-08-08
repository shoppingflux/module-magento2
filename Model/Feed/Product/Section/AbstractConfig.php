<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section;

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
}
