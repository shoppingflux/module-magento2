<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value\Renderer;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value\AbstractRenderer;

class Text extends AbstractRenderer
{
    public function getSortOrder()
    {
        return 1000000;
    }

    public function isAppliableToAttribute(AbstractAttribute $attribute)
    {
        return $this->isTextAttribute($attribute);
    }
}
