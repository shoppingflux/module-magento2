<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Adapter;

use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use ShoppingFeed\Feed\Product\AbstractProduct as AbstractExportedProduct;
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
     * @param StoreInterface $store
     * @param CatalogProduct $product
     * @param AbstractAttribute|null $attribute
     * @param mixed|null $defaultValue
     * @return mixed|null
     */
    protected function getCatalogProductValue(StoreInterface $store, CatalogProduct $product, $attribute, $defaultValue)
    {
        $value = null;

        if ($attribute instanceof AbstractAttribute) {
            $value = $this->getCatalogProductAttributeValue($store, $product, $attribute);
        }

        return (null !== $value)
            ? $value
            : ((null !== $defaultValue) ? $defaultValue : null);
    }

    public function getProductData(StoreInterface $store, RefreshableProduct $product)
    {
        $config = $this->getConfig();
        $catalogProduct = $product->getCatalogProduct();

        $fees = $this->getCatalogProductValue(
            $store,
            $catalogProduct,
            $config->getFeesAttribute($store),
            $config->getDefaultFees($store)
        );

        $delay = $this->getCatalogProductValue(
            $store,
            $catalogProduct,
            $config->getDelayAttribute($store),
            $config->getDefaultDelay($store)
        );

        return [
            self::KEY_CARRIER_NAME => (string) $this->getCatalogProductValue(
                $store,
                $catalogProduct,
                $config->getCarrierNameAttribute($store),
                $config->getDefaultCarrierName($store)
            ),
            self::KEY_FEES => is_numeric($fees) ? (float) $fees : null,
            self::KEY_DELAY => is_int($delay) || ctype_digit((string) $delay) ? (int) $delay : null,
        ];
    }

    public function exportBaseProductData(
        StoreInterface $store,
        array $productData,
        AbstractExportedProduct $exportedProduct
    ) {
        if (isset($productData[self::KEY_FEES])) {
            $exportedProduct->addShipping(
                $productData[self::KEY_FEES],
                !$this->getConfig()->shouldUseOldExportBehavior($store)
                    ? ($productData[self::KEY_DELAY] ?? '')
                    : ($productData[self::KEY_CARRIER_NAME] ?? '')
            );
        }

        if (isset($productData[self::KEY_DELAY])) {
            $exportedProduct->setAttribute('shipping_delay', $productData[self::KEY_DELAY]);
        }
    }

    public function describeProductData(StoreInterface $store, array $productData)
    {
        return $this->describeRawProductData(
            [
                self::KEY_CARRIER_NAME => __('Carrier Name'),
                self::KEY_FEES => __('Fees'),
                self::KEY_DELAY => __('Delay'),
            ],
            $productData
        );
    }
}
