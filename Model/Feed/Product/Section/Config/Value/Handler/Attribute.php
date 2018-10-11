<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Config\Value\Handler;

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
        parent::__construct(UiText::NAME, $attributeSource->getRenderableAttributeOptionArray(false));
    }

    public function prepareRawValueForUse($value, $defaultValue, $isRequired)
    {
        $attributeCode = parent::prepareRawValueForUse($value, $defaultValue, $isRequired);
        return (null !== $attributeCode) ? $this->attributeSource->getRenderableAttributeByCode($attributeCode) : null;
    }
}
