<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Adapter;

use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Store\Model\StoreManagerInterface;
use ShoppingFeed\Feed\Product\AbstractProduct as AbstractExportedProduct;
use ShoppingFeed\Feed\Product\Product as ExportedProduct;
use ShoppingFeed\Feed\Product\ProductVariation as ExportedVariation;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\SourceInterface as AttributeSourceInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value\RendererPoolInterface as AttributeRendererPoolInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractAdapter;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Config\AttributesInterface as ConfigInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Type\Attributes as Type;
use ShoppingFeed\Manager\Model\Feed\RefreshableProduct;


/**
 * @method ConfigInterface getConfig()
 */
class Attributes extends AbstractAdapter implements AttributesInterface
{
    const KEY_SKU = 'sku';
    const KEY_BRAND = 'brand';
    const KEY_DESCRIPTION = 'description';
    const KEY_SHORT_DESCRIPTION = 'short_description';
    const KEY_GTIN = 'gtin';
    const KEY_NAME = 'name';
    const KEY_ATTRIBUTE_MAP = 'attribute_map';
    const KEY_CONFIGURABLE_ATTRIBUTES = 'configurable_attributes';

    /**
     * @var AttributeSourceInterface
     */
    private $attributeSource;

    /**
     * @param StoreManagerInterface $storeManager
     * @param AttributeRendererPoolInterface $attributeRendererPool
     * @param AttributeSourceInterface $attributeSource
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        AttributeRendererPoolInterface $attributeRendererPool,
        AttributeSourceInterface $attributeSource
    ) {
        $this->attributeSource = $attributeSource;
        parent::__construct($storeManager, $attributeRendererPool);
    }

    public function getSectionType()
    {
        return Type::CODE;
    }

    public function prepareLoadableProductCollection(StoreInterface $store, ProductCollection $productCollection)
    {
        $productCollection->addAttributeToSelect([ 'sku', 'name' ]);

        foreach ($this->getConfig()->getAllAttributes($store) as $attribute) {
            $productCollection->addAttributeToSelect($attribute->getAttributeCode());
        }

        foreach ($this->attributeSource->getConfigurableAttributes() as $key => $attribute) {
            $productCollection->addAttributeToSelect($attribute->getAttributeCode());
        }
    }

    public function getProductData(StoreInterface $store, RefreshableProduct $product)
    {
        $config = $this->getConfig();
        $catalogProduct = $product->getCatalogProduct();
        $productId = (int) $catalogProduct->getId();
        $productSku = $catalogProduct->getSku();

        $data = [
            self::KEY_SKU => $config->shouldUseProductIdForSku($store) ? $productId : $productSku,
            self::KEY_NAME => $catalogProduct->getName(),
        ];

        if ($attribute = $config->getBrandAttribute($store)) {
            $data[self::KEY_BRAND] = $this->getCatalogProductAttributeValue($catalogProduct, $attribute);
        }

        if ($attribute = $config->getDescriptionAttribute($store)) {
            $data[self::KEY_DESCRIPTION] = $this->getCatalogProductAttributeValue($catalogProduct, $attribute);
        }

        if ($attribute = $config->getShortDescriptionAttribute($store)) {
            $data[self::KEY_SHORT_DESCRIPTION] = $this->getCatalogProductAttributeValue($catalogProduct, $attribute);
        }

        if ($attribute = $config->getGtinAttribute($store)) {
            $data[self::KEY_GTIN] = $this->getCatalogProductAttributeValue($catalogProduct, $attribute);
        }

        foreach ($config->getAttributeMap($store) as $key => $attribute) {
            $value = $this->getCatalogProductAttributeValue($catalogProduct, $attribute);
            $data[self::KEY_ATTRIBUTE_MAP][$key] = $value;
        }

        if (ProductType::TYPE_SIMPLE === $catalogProduct->getTypeId()) {
            foreach ($this->attributeSource->getConfigurableAttributes() as $key => $attribute) {
                $value = $this->getCatalogProductAttributeValue($catalogProduct, $attribute);
                $data[self::KEY_CONFIGURABLE_ATTRIBUTES][$key] = $value;
            }
        }

        return $data;
    }

    public function exportBaseProductData(
        StoreInterface $store,
        array $productData,
        AbstractExportedProduct $exportedProduct
    ) {
        if (isset($productData[self::KEY_SKU])) {
            $exportedProduct->setReference($productData[self::KEY_SKU]);
        }

        if (isset($productData[self::KEY_GTIN])) {
            $exportedProduct->setGtin($productData[self::KEY_GTIN]);
        }

        if (isset($productData[self::KEY_ATTRIBUTE_MAP])) {
            foreach ($this->getConfig()->getAttributeMap($store) as $key => $attribute) {
                if (isset($productData[self::KEY_ATTRIBUTE_MAP][$key])) {
                    $exportedProduct->setAttribute($key, $productData[self::KEY_ATTRIBUTE_MAP][$key]);
                }
            }
        }
    }

    public function exportMainProductData(
        StoreInterface $store,
        array $productData,
        ExportedProduct $exportedProduct
    ) {
        if (isset($productData[self::KEY_BRAND])) {
            $exportedProduct->setBrand($productData[self::KEY_BRAND]);
        }

        if (isset($productData[self::KEY_DESCRIPTION])) {
            $exportedProduct->setDescription(
                $productData[self::KEY_DESCRIPTION],
                $productData[self::KEY_SHORT_DESCRIPTION] ?? ''
            );
        }

        if (isset($productData[self::KEY_NAME])) {
            $exportedProduct->setName($productData[self::KEY_NAME]);
        }
    }

    public function exportVariationProductData(
        StoreInterface $store,
        array $productData,
        array $configurableAttributeCodes,
        ExportedVariation $exportedVariation
    ) {
        if (isset($productData[self::KEY_CONFIGURABLE_ATTRIBUTES])) {
            foreach ($configurableAttributeCodes as $attributeCode) {
                if (isset($productData[self::KEY_CONFIGURABLE_ATTRIBUTES][$attributeCode])) {
                    $exportedVariation->setAttribute(
                        $attributeCode,
                        $productData[self::KEY_CONFIGURABLE_ATTRIBUTES][$attributeCode],
                        true
                    );
                }
            }
        }
    }
}
