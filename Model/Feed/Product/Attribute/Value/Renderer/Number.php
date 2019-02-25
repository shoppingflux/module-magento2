<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value\Renderer;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value\AbstractRenderer;

class Number extends AbstractRenderer
{
    public function getSortOrder()
    {
        return 500000;
    }

    public function isAppliableToAttribute(AbstractAttribute $attribute)
    {
        return $this->isDecimalAttribute($attribute)
            || ($this->isTextAttribute($attribute)
                && in_array($attribute->getFrontendClass(), [ 'validate-digits', 'validate-number' ], true));
    }

    public function renderAttributeValue(StoreInterface $store, AbstractAttribute $attribute, $value)
    {
        return (null !== $value) && ('' !== $value) ? (string) (float) $value : $value;
    }
}
