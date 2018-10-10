<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value\Renderer;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value\AbstractRenderer;

class Date extends AbstractRenderer
{
    public function getSortOrder()
    {
        return 20000;
    }

    public function isAppliableToAttribute(AbstractAttribute $attribute)
    {
        return $this->isDateAttribute($attribute);
    }

    public function renderAttributeValue(AbstractAttribute $attribute, $value)
    {
        return $value;
    }
}
