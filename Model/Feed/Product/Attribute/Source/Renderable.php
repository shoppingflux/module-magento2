<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Attribute\Source;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\AbstractSource;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\SourceInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value\RendererPoolInterface as AttributeRendererPoolInterface;

class Renderable extends AbstractSource
{
    /**
     * @var AttributeRendererPoolInterface
     */
    private $attributeRendererPool;

    /**
     * @var SourceInterface
     */
    private $fullAttributeSource;

    /**
     * @var string[]
     */
    private $excludedAttributeCodes;

    /**
     * @var array|null
     */
    private $renderableAttributes = null;

    /**
     * @param AttributeRendererPoolInterface $attributeRendererPool
     * @param SourceInterface $fullAttributeSource
     * @param string[] $excludedAttributeCodes
     */
    public function __construct(
        AttributeRendererPoolInterface $attributeRendererPool,
        SourceInterface $fullAttributeSource,
        array $excludedAttributeCodes = array()
    ) {
        $this->attributeRendererPool = $attributeRendererPool;
        $this->fullAttributeSource = $fullAttributeSource;
        $this->excludedAttributeCodes = $excludedAttributeCodes;
    }

    /**
     * @return AbstractAttribute[]
     */
    public function getAttributesByCode()
    {
        if (!is_array($this->renderableAttributes)) {
            foreach ($this->fullAttributeSource->getAttributesByCode() as $attributeCode => $attribute) {
                if (!in_array($attributeCode, $this->excludedAttributeCodes, true)
                    && $this->attributeRendererPool->hasAttributeRenderableValues($attribute)
                ) {
                    $this->renderableAttributes[$attributeCode] = $attribute;
                }
            }
        }

        return $this->renderableAttributes;
    }
}
