<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value;

use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;

interface RendererInterface
{
    /**
     * @return int
     */
    public function getSortOrder();

    /**
     * @param AbstractAttribute $attribute
     * @return bool
     */
    public function isAppliableToAttribute(AbstractAttribute $attribute);

    /**
     * @param StoreInterface $store
     * @param CatalogProduct $product
     * @param AbstractAttribute $attribute
     * @return string|null
     */
    public function getProductAttributeValue(
        StoreInterface $store,
        CatalogProduct $product,
        AbstractAttribute $attribute
    );
}
