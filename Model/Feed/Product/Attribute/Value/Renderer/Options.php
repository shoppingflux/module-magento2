<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value\Renderer;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
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
     * @param StoreInterface $store
     * @param AbstractAttribute $attribute
     * @param mixed $value
     * @return string
     * @throws LocalizedException
     */
    public function renderAttributeValue(StoreInterface $store, AbstractAttribute $attribute, $value)
    {
        if (!is_array($value)) {
            $value = array_filter(explode(',', $value), [ $this, 'isDefinedValue' ]);
        }

        $source = $attribute->getSource();

        foreach ($value as $key => $subValue) {
            $subLabel = $source->getOptionText($subValue);

            if (is_array($subLabel)) {
                if (isset($subLabel['label'])) {
                    $subLabel = $subLabel['label'];
                } else {
                    $subLabel = false;
                }
            }

            if (false !== $subLabel) {
                $value[$key] = (string) $subLabel;
            } else {
                unset($value[$key]);
            }
        }

        return empty($value) ? null : implode(', ', $value);
    }
}
