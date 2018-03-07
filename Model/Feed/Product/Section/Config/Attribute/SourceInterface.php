<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Config\Attribute;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;


interface SourceInterface
{
    /**
     * @return AbstractAttribute[]
     */
    public function getAttributes();

    /**
     * @param bool $withEmpty
     * @return array
     */
    public function getAttributeOptionArray($withEmpty = true);

    /**
     * @param string $attributeCode
     * @return AbstractAttribute|false
     */
    public function getAttributeByCode($attributeCode);
}
