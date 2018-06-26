<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value\Renderer;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value\AbstractRenderer;


class Boolean extends AbstractRenderer
{
    public function getSortOrder()
    {
        return 10000;
    }

    public function isAppliableToAttribute(AbstractAttribute $attribute)
    {
        return $this->isBooleanAttribute($attribute);
    }

    public function renderAttributeValue(AbstractAttribute $attribute, $value)
    {
        return (null !== $value) && ('' !== $value) ? (string) ($value ? __('Yes') : __('No')) : '';
    }
}
