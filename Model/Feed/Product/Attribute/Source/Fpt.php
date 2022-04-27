<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Attribute\Source;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\AbstractSource;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\SourceInterface;

class Fpt extends AbstractSource
{
    /**
     * @var SourceInterface
     */
    private $fullAttributeSource;

    /**
     * @var array|null
     */
    private $fptAttributes = null;

    /**
     * @param SourceInterface $fullAttributeSource
     */
    public function __construct(SourceInterface $fullAttributeSource)
    {
        $this->fullAttributeSource = $fullAttributeSource;
    }

    /**
     * @return AbstractAttribute[]
     */
    public function getAttributesByCode()
    {
        if (!is_array($this->fptAttributes)) {
            $this->fptAttributes = [];

            foreach ($this->fullAttributeSource->getAttributesByCode() as $attributeCode => $attribute) {
                if ($attribute->getFrontendInput() === 'weee') {
                    $this->fptAttributes[$attributeCode] = $attribute;
                }
            }
        }

        return $this->fptAttributes;
    }
}
