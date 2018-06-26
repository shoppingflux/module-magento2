<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Attribute;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;


interface SourceInterface
{
    /**
     * @return AbstractAttribute[]
     */
    public function getConfigurableAttributes();

    /**
     * @return AbstractAttribute[]
     */
    public function getRenderableAttributes();

    /**
     * @param bool $withEmpty
     * @return array
     */
    public function getRenderableAttributeOptionArray($withEmpty = true);

    /**
     * @param string $attributeCode
     * @return AbstractAttribute|false
     */
    public function getRenderableAttributeByCode($attributeCode);
}
