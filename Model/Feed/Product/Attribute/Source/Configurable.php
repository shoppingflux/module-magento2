<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Attribute\Source;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttributeResource;
use Magento\ConfigurableProduct\Model\ConfigurableAttributeHandler;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\AbstractSource;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\SourceInterface;

class Configurable extends AbstractSource
{
    /**
     * @var ConfigurableAttributeHandler
     */
    private $configurableAttributeHandler;

    /**
     * @var SourceInterface
     */
    private $fullAttributeSource;

    /**
     * @var array|null
     */
    private $configurableAttributes = null;

    /**
     * @param ConfigurableAttributeHandler $configurableAttributeHandler
     * @param SourceInterface $fullAttributeSource
     */
    public function __construct(
        ConfigurableAttributeHandler $configurableAttributeHandler,
        SourceInterface $fullAttributeSource
    ) {
        $this->configurableAttributeHandler = $configurableAttributeHandler;
        $this->fullAttributeSource = $fullAttributeSource;
    }

    /**
     * @return AbstractAttribute[]
     */
    public function getAttributesByCode()
    {
        if (!is_array($this->configurableAttributes)) {
            $attributes = $this->fullAttributeSource->getAttributesByCode();
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
}
