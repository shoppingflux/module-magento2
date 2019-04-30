<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value\Renderer;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
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
     * @param StoreInterface $store
     * @param AbstractAttribute $attribute
     * @param mixed $value
     * @return string|null
     * @throws LocalizedException
     */
    public function renderAttributeValue(StoreInterface $store, AbstractAttribute $attribute, $value)
    {
        if (!$this->isUndefinedValue($value)) {
            $label = $attribute->getSource()->getOptionText($value);

            if (false !== $label) {
                if (!is_array($label)) {
                    return (string) $label;
                } elseif (isset($label['label'])) {
                    return (string) $label['label'];
                }
            }
        }

        return null;
    }
}
