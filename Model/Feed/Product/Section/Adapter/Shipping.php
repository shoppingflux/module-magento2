<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Adapter;

use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
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

            self::KEY_FEES => $this->getCatalogProductValue(
                $catalogProduct,
                $config->getFeesAttribute($store),
                $config->getDefaultFees($store)
            ),

            self::KEY_DELAY => $this->getCatalogProductValue(
                $catalogProduct,
                $config->getDelayAttribute($store),
                $config->getDefaultDelay($store)
            ),
        ];
    }
}
