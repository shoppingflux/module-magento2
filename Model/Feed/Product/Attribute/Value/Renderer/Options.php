<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value\Renderer;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value\AbstractRenderer;


class Options extends AbstractRenderer
{
    public function getSortOrder()
    {
        return 50000;
    }

    public function isAppliableToAttribute(AbstractAttribute $attribute)
    {
        return $this->isOptionAttribute($attribute) && $this->isMultipleValuesAttribute($attribute);
    }

    /**
     * @param AbstractAttribute $attribute
     * @param mixed $value
     * @return string
     * @throws LocalizedException
     */
    public function renderAttributeValue(AbstractAttribute $attribute, $value)
    {
        if (!is_array($value)) {
            $value = array_filter(explode(',', $value), [ $this, 'isDefinedValue' ]);
        }

        $source = $attribute->getSource();

        foreach ($value as $key => $subValue) {
            $value[$key] = (string) $source->getOptionText($subValue);
        }

        return implode(', ', $value);
    }
}
