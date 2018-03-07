<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;


interface RendererPoolInterface
{
    /**
     * @return RendererInterface[]
     */
    public function getRenderers();

    /**
     * @return RendererInterface[]
     */
    public function getSortedRenderers();

    /**
     * @param string $code
     * @return RendererInterface
     */
    public function getRendererByCode($code);

    /**
     * @param AbstractAttribute $attribute
     * @return bool
     */
    public function hasAttributeRenderableValues(AbstractAttribute $attribute);
}
