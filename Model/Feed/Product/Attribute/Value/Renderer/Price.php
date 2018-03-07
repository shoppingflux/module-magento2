<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value\Renderer;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value\AbstractRenderer;


class Price extends AbstractRenderer
{
    public function getSortOrder()
    {
        return 60000;
    }

    public function isAppliableToAttribute(AbstractAttribute $attribute)
    {
        return $this->isPriceAttribute($attribute);
    }

    public function renderAttributeValue(AbstractAttribute $attribute, $value)
    {
        return !$this->isUndefinedValue($value) ? (string) round((float) $value, 4) : null;
    }
}
