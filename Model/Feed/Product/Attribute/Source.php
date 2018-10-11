<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Attribute;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttributeResource;
use Magento\Catalog\Model\ResourceModel\ProductFactory as CatalogProductResourceFactory;
use Magento\ConfigurableProduct\Model\ConfigurableAttributeHandler;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value\RendererPoolInterface as AttributeRendererPoolInterface;

class Source implements SourceInterface
{
    /**
     * @var CatalogProductResourceFactory
     */
    private $catalogProductResourceFactory;

    /**
     * @var ConfigurableAttributeHandler
     */
    private $configurableAttributeHandler;

    /**
     * @var AttributeRendererPoolInterface
     */
    private $attributeRendererPool;

    /**
     * @var AbstractAttribute[]
     */
    private $configurableAttributes;

    /**
     * @var AbstractAttribute[]
     */
    private $renderableAttributes;

    /**
     * @var array
     */
    private $renderableAttributeOptionArray;

    /**
     * @var string[]
     */
    private $excludedRenderableAttributes;

    /**
     * @param CatalogProductResourceFactory $catalogProductResourceFactory
     * @param ConfigurableAttributeHandler $configurableAttributeHandler
     * @param AttributeRendererPoolInterface $attributeRendererPool
     * @param string[] $excludedRenderableAttributes
     */
    public function __construct(
        CatalogProductResourceFactory $catalogProductResourceFactory,
        ConfigurableAttributeHandler $configurableAttributeHandler,
        AttributeRendererPoolInterface $attributeRendererPool,
        array $excludedRenderableAttributes = []
    ) {
        $this->catalogProductResourceFactory = $catalogProductResourceFactory;
        $this->configurableAttributeHandler = $configurableAttributeHandler;
        $this->attributeRendererPool = $attributeRendererPool;
        $this->excludedRenderableAttributes = array_filter($excludedRenderableAttributes);
    }

    /**
     * @return AbstractAttribute[]
     */
    private function getAttributesByCode()
    {
        $catalogProductResource = $this->catalogProductResourceFactory->create();
        $attributes = $catalogProductResource->getAttributesByCode();

        if (empty($attributes)) {
            $catalogProductResource->loadAllAttributes();
            $attributes = $catalogProductResource->getAttributesByCode();
        }

        return $attributes;
    }

    public function getConfigurableAttributes()
    {
        if (!is_array($this->configurableAttributes)) {
            $attributes = $this->getAttributesByCode();
            $this->configurableAttributes = [];
            $configurableAttributeCollection = $this->configurableAttributeHandler->getApplicableAttributes();

            /** @var EavAttributeResource $configurableAttribute */
            foreach ($configurableAttributeCollection->getItems() as $configurableAttribute) {
                if ($this->configurableAttributeHandler->isAttributeApplicable($configurableAttribute)) {
                    $attributeCode = $configurableAttribute->getAttributeCode();

                    if (isset($attributes[$attributeCode])) {
                        $this->configurableAttributes[$attributeCode] = $attributes[$attributeCode];
                    }
                }
            }
        }

        return $this->configurableAttributes;
    }

    public function getRenderableAttributes()
    {
        if (!is_array($this->renderableAttributes)) {
            $attributes = $this->getAttributesByCode();

            foreach ($attributes as $attributeCode => $attribute) {
                if (in_array($attributeCode, $this->excludedRenderableAttributes, true)
                    || !$this->attributeRendererPool->hasAttributeRenderableValues($attribute)
                ) {
                    unset($attributes[$attributeCode]);
                }
            }

            $this->renderableAttributes = $attributes;
        }

        return $this->renderableAttributes;
    }

    public function getRenderableAttributeOptionArray($withEmpty = false)
    {
        if (!is_array($this->renderableAttributeOptionArray)) {
            $this->renderableAttributeOptionArray = [];

            foreach ($this->getRenderableAttributes() as $attributeCode => $productAttribute) {
                $this->renderableAttributeOptionArray[] = [
                    'value' => $attributeCode,
                    'label' => __('%1 (%2)', $attributeCode, $productAttribute->getFrontend()->getLabel()),
                ];
            }
        }

        $result = $this->renderableAttributeOptionArray;

        if ($withEmpty) {
            array_unshift($result, [ 'value' => '', 'label' => __('None') ]);
        }

        return $result;
    }

    public function getRenderableAttributeByCode($attributeCode)
    {
        $attributes = $this->getRenderableAttributes();
        return isset($attributes[$attributeCode]) ? $attributes[$attributeCode] : false;
    }
}
