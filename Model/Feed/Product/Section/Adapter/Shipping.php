<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Adapter;

use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use ShoppingFeed\Feed\Product\AbstractProduct as AbstractExportedProduct;
use ShoppingFeed\Feed\Product\Product as ExportedProduct;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractAdapter;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Config\ShippingInterface as ConfigInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Type\Shipping as Type;
use ShoppingFeed\Manager\Model\Feed\RefreshableProduct;


/**
 * @method ConfigInterface getConfig()
 */
class Shipping extends AbstractAdapter implements ShippingInterface
{
    const KEY_CARRIER_NAME = 'shipping_name';
    const KEY_FEES = 'shipping_fees';
    const KEY_DELAY = 'shipping_delay';

    public function getSectionType()
    {
        return Type::CODE;
    }

    public function prepareLoadableProductCollection(StoreInterface $store, ProductCollection $productCollection)
    {
        foreach ($this->getConfig()->getAllAttributes($store) as $attribute) {
            $productCollection->addAttributeToSelect($attribute->getAttributeCode());
        }
    }

    /**
     * @param CatalogProduct $product
     * @param AbstractAttribute|null $attribute
     * @param mixed|null $defaultValue
     * @return string|null
     */
    protected function getCatalogProductValue(CatalogProduct $product, $attribute, $defaultValue)
    {
        $value = null;

        if ($attribute instanceof AbstractAttribute) {
            $value = $this->getCatalogProductAttributeValue($product, $attribute);
        }

        return (null !== $value)
            ? (string) $value
            : (null !== $defaultValue) ? (string) $defaultValue : null;
    }

    public function getProductData(StoreInterface $store, RefreshableProduct $product)
    {
        $config = $this->getConfig();
        $catalogProduct = $product->getCatalogProduct();

        return [
            self::KEY_CARRIER_NAME => $this->getCatalogProductValue(
                $catalogProduct,
                $config->getCarrierNameAttribute($store),
                $config->getDefaultCarrierName($store)
            ),

            self::KEY_FEES => (float) $this->getCatalogProductValue(
                $catalogProduct,
                $config->getFeesAttribute($store),
                $config->getDefaultFees($store)
            ),

            self::KEY_DELAY => (int) $this->getCatalogProductValue(
                $catalogProduct,
                $config->getDelayAttribute($store),
                $config->getDefaultDelay($store)
            ),
        ];
    }

    public function exportBaseProductData(
        StoreInterface $store,
        array $productData,
        AbstractExportedProduct $exportedProduct
    ) {
        if (isset($productData[self::KEY_FEES]) && isset($productData[self::KEY_DELAY])) {
            $exportedProduct->addShipping(
                $productData[self::KEY_FEES],
                $productData[self::KEY_DELAY],
                $productData[self::KEY_CARRIER_NAME] ?? ''
            );
        }
    }
}
