<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Attribute;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;

interface SourceInterface
{
    /**
     * @return AbstractAttribute[]
     */
    public function getAttributesByCode();

    /**
     * @param bool $withEmpty
     * @return array
     */
    public function getAttributeOptionArray($withEmpty = true);

    /**
     * @param string $code
     * @return AbstractAttribute|null
     */
    public function getAttribute($code);
}
