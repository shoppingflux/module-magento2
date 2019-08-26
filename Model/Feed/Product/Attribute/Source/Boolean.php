<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Attribute\Source;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\AbstractSource;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\SourceInterface;

class Boolean extends AbstractSource
{
    /**
     * @var SourceInterface
     */
    private $fullAttributeSource;

    /**
     * @var array|null
     */
    private $booleanAttributes = null;

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
        if (!is_array($this->booleanAttributes)) {
            $this->booleanAttributes = [];

            foreach ($this->fullAttributeSource->getAttributesByCode() as $attributeCode => $attribute) {
                if ($attribute->getFrontendInput() === 'boolean') {
                    $this->booleanAttributes[$attributeCode] = $attribute;
                }
            }
        }

        return $this->booleanAttributes;
    }
}
