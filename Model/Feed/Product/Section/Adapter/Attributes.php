<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Adapter;

use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractAdapter;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Config\AttributesInterface as ConfigInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Type\Attributes as Type;
use ShoppingFeed\Manager\Model\Feed\RefreshableProduct;


/**
 * @method ConfigInterface getConfig()
 */
class Attributes extends AbstractAdapter implements AttributesInterface
{
    const KEY_ID = 'id';
    const KEY_SKU = 'sku';
    const KEY_ORIGINAL_SKU = 'magento_sku';
    const KEY_URL = 'url';

    public function getSectionType()
    {
        return Type::CODE;
    }

    public function getProductData(StoreInterface $store, RefreshableProduct $product)
    {
        $config = $this->getConfig();
        $catalogProduct = $product->getCatalogProduct();
        $productId = $catalogProduct->getId();
        $productSku = $catalogProduct->getSku();

        $data = [
            self::KEY_ID => $productId,
            self::KEY_SKU => $config->shouldUseProductIdForSku($store) ? $productId : $productSku,
            self::KEY_ORIGINAL_SKU => $productSku,
            self::KEY_URL => $catalogProduct->getProductUrl(false),
        ];

        foreach ($config->getAttributeMap($store) as $key => $attribute) {
            $data[$key] = $this->getCatalogProductAttributeValue($catalogProduct, $attribute);
        }

        return $data;
    }

    public function adaptChildProductData(StoreInterface $store, array $productData)
    {
        if (isset($productData[self::KEY_URL])) {
            unset($productData[self::KEY_URL]);
        }

        return $productData;
    }
}
