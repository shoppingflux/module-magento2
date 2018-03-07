<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value\Renderer;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value\AbstractRenderer;


class Option extends AbstractRenderer
{
    public function getSortOrder()
    {
        return 40000;
    }

    public function isAppliableToAttribute(AbstractAttribute $attribute)
    {
        return $this->isOptionAttribute($attribute) && !$this->isMultipleValuesAttribute($attribute);
    }

    /**
     * @param AbstractAttribute $attribute
     * @param mixed $value
     * @return string|null
     * @throws LocalizedException
     */
    public function renderAttributeValue(AbstractAttribute $attribute, $value)
    {
        return !$this->isUndefinedValue($value) ? (string) $attribute->getSource()->getOptionText($value) : null;
    }
}
