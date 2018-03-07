<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Config\Attribute;

use Magento\Catalog\Model\ResourceModel\Product as CatalogProductResource;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value\RendererPoolInterface as AttributeRendererPoolInterface;


class Source implements SourceInterface
{
    /**
     * @var CatalogProductResource $catalogProductResource
     */
    private $catalogProductResource;

    /**
     * @var AttributeRendererPoolInterface
     */
    private $attributeRendererPool;

    /**
     * @var AbstractAttribute[]
     */
    private $productAttributes;

    /**
     * @var array
     */
    private $productAttributeOptionArray;

    /**
     * @var string[]
     */
    private $excludedAttributes;

    /**
     * @param CatalogProductResource $catalogProductResource
     * @param AttributeRendererPoolInterface $attributeRendererPool
     * @param string[] $excludedAttributes
     */
    public function __construct(
        CatalogProductResource $catalogProductResource,
        AttributeRendererPoolInterface $attributeRendererPool,
        array $excludedAttributes = []
    ) {
        $this->catalogProductResource = $catalogProductResource;
        $this->attributeRendererPool = $attributeRendererPool;
        $this->excludedAttributes = array_filter($excludedAttributes);
    }

    public function getAttributes()
    {
        if (!is_array($this->productAttributes)) {
            $attributes = $this->catalogProductResource->getAttributesByCode();

            if (empty($attributes)) {
                $this->catalogProductResource->loadAllAttributes();
                $attributes = $this->catalogProductResource->getAttributesByCode();
            }

            foreach ($attributes as $attributeCode => $attribute) {
                if (in_array($attributeCode, $this->excludedAttributes, true)
                    || !$this->attributeRendererPool->hasAttributeRenderableValues($attribute)
                ) {
                    unset($attributes[$attributeCode]);
                }
            }

            $this->productAttributes = $attributes;
        }

        return $this->productAttributes;
    }

    public function getAttributeOptionArray($withEmpty = false)
    {
        if (!is_array($this->productAttributeOptionArray)) {
            $this->productAttributeOptionArray = [];

            foreach ($this->getAttributes() as $attributeCode => $productAttribute) {
                $this->productAttributeOptionArray[] = [
                    'value' => $attributeCode,
                    'label' => __('%1 (%2)', $attributeCode, $productAttribute->getFrontend()->getLabel()),
                ];
            }
        }

        $result = $this->productAttributeOptionArray;

        if ($withEmpty) {
            array_unshift($result, [ 'value' => '', 'label' => __('None') ]);
        }

        return $result;
    }

    public function getAttributeByCode($attributeCode)
    {
        $attributes = $this->getAttributes();
        return isset($attributes[$attributeCode]) ? $attributes[$attributeCode] : false;
    }
}
