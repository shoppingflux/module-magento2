<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value\Renderer;

use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Catalog\Model\Product\Attribute\Frontend\Image as ImageFrontend;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value\AbstractRenderer;
use ShoppingFeed\Manager\Model\Feed\Product\Constants as ProductConstants;


class Image extends AbstractRenderer
{
    public function getSortOrder()
    {
        return 30000;
    }

    public function isAppliableToAttribute(AbstractAttribute $attribute)
    {
        return $this->isImageAttribute($attribute);
    }

    public function getProductAttributeValue(CatalogProduct $product, AbstractAttribute $attribute)
    {
        $imageFile = trim($product->getData($attribute->getAttributeCode()));

        if (!empty($imageFile) && (ProductConstants::EMPTY_IMAGE_VALUE !== $imageFile)) {
            $frontend = $attribute->getFrontend();
            return ($frontend instanceof ImageFrontend) ? $frontend->getUrl($product) : null;
        }

        return null;
    }
}
