<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Config\Value\Handler;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Ui\Component\Form\Element\DataType\Text as UiText;
use ShoppingFeed\Manager\Model\Config\Value\Handler\Option as OptionHandler;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\SourceInterface as AttributeSourceInterface;

class Attribute extends OptionHandler
{
    const TYPE_CODE = 'product_attribute';

    /**
     * @var AttributeSourceInterface
     */
    private $attributeSource;

    public function __construct(AttributeSourceInterface $attributeSource)
    {
        $this->attributeSource = $attributeSource;
        parent::__construct(UiText::NAME, $attributeSource->getAttributeOptionArray(false));
    }

    public function isEqualValues($valueA, $valueB)
    {
        return parent::isEqualValueLists((array) $valueA, (array) $valueB);
    }

    public function isEqualValueLists(array $valuesA, array $valuesB)
    {
        $codesA = [];
        $codesB = [];

        foreach ($valuesA as $attribute) {
            if ($attribute instanceof AbstractAttribute) {
                $codesA[] = $attribute->getAttributeCode();
            } else {
                $codesA[] = (string) $attribute;
            }
        }

        foreach ($valuesB as $attribute) {
            if ($attribute instanceof AbstractAttribute) {
                $codesB[] = $attribute->getAttributeCode();
            } else {
                $codesB[] = (string) $attribute;
            }
        }

        sort($codesA);
        sort($codesB);

        return $codesA === $codesB;
    }

    public function prepareRawValueForUse($value, $defaultValue, $isRequired)
    {
        $attributeCode = parent::prepareRawValueForUse($value, $defaultValue, $isRequired);
        return (null !== $attributeCode) ? $this->attributeSource->getAttribute($attributeCode) : null;
    }
}
