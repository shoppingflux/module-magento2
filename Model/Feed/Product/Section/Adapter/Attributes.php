<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Adapter;

use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\Entity\Type as EavEntityType;
use Magento\Eav\Model\Entity\TypeFactory as EavEntityTypeFactory;
use Magento\Framework\DataObject;
use Magento\Framework\UrlInterface;
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
use ShoppingFeed\Manager\Model\LabelledValueFactory;

/**
 * @method ConfigInterface getConfig()
 */
class Attributes extends AbstractAdapter implements AttributesInterface
{
    const KEY_SKU = 'sku';
    const KEY_NAME = 'name';
    const KEY_GTIN = 'gtin';
    const KEY_BRAND = 'brand';
    const KEY_DESCRIPTION = 'description';
    const KEY_SHORT_DESCRIPTION = 'short_description';
    const KEY_URL = 'url';
    const KEY_WEIGHT = 'weight';
    const KEY_DYNAMIC_WEIGHT = 'dynamic_weight';
    const KEY_ATTRIBUTE_MAP = 'attribute_map';
    const KEY_CONFIGURABLE_ATTRIBUTES = 'configurable_attributes';
    const KEY_ATTRIBUTE_SET = 'attribute_set';

    const DEFAULT_RESERVED_ATTRIBUTE_CODE_SUFFIX = '_attribute';

    /**
     * @var EavEntityTypeFactory
     */
    private $eavEntityTypeFactory;

    /**
     * @var UrlInterface
     */
    private $frontendUrlBuilder;

    /**
     * @var AttributeSourceInterface
     */
    private $configurableAttributeSource;

    /**
     * @var string
     */
    private $reservedAttributeCodeSuffix;

    /**
     * @var EavEntityType|null
     */
    private $productEavEntityType = null;

    /**
     * @var string[]|null
     */
    private $attributeSetNames = null;

    /**
     * @param StoreManagerInterface $storeManager
     * @param EavEntityTypeFactory $eavEntityTypeFactory
     * @param LabelledValueFactory $labelledValueFactory
     * @param AttributeRendererPoolInterface $attributeRendererPool
     * @param UrlInterface $frontendUrlBuilder
     * @param AttributeSourceInterface $configurableAttributeSource
     * @param string $reservedAttributeCodeSuffix
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        EavEntityTypeFactory $eavEntityTypeFactory,
        LabelledValueFactory $labelledValueFactory,
        AttributeRendererPoolInterface $attributeRendererPool,
        UrlInterface $frontendUrlBuilder,
        AttributeSourceInterface $configurableAttributeSource,
        string $reservedAttributeCodeSuffix = self::DEFAULT_RESERVED_ATTRIBUTE_CODE_SUFFIX
    ) {
        $this->eavEntityTypeFactory = $eavEntityTypeFactory;
        $this->frontendUrlBuilder = $frontendUrlBuilder;
        $this->configurableAttributeSource = $configurableAttributeSource;
        $this->reservedAttributeCodeSuffix = $reservedAttributeCodeSuffix;
        parent::__construct($storeManager, $labelledValueFactory, $attributeRendererPool);
    }

    public function getSectionType()
    {
        return Type::CODE;
    }

    public function prepareLoadableProductCollection(StoreInterface $store, ProductCollection $productCollection)
    {
        $productCollection->addAttributeToSelect([ 'sku', 'name', 'url_key' ]);

        foreach ($this->getConfig()->getAllAttributes($store) as $attribute) {
            $productCollection->addAttributeToSelect($attribute->getAttributeCode());
        }

        foreach ($this->configurableAttributeSource->getAttributesByCode() as $code => $attribute) {
            $productCollection->addAttributeToSelect($code);
        }

        $productCollection->addUrlRewrite();
    }

    public function prepareLoadedProductCollection(StoreInterface $store, ProductCollection $productCollection)
    {
        $weeeAttributes = [];

        foreach ($this->getConfig()->getAllAttributes($store) as $attribute) {
            if ('weee' === $attribute->getFrontendInput()) {
                $weeeAttributes[] = $attribute;
            }
        }

        if (!empty($weeeAttributes)) {
            /** @var CatalogProduct $product */
            foreach ($productCollection as $product) {
                foreach ($weeeAttributes as $weeeAttribute) {
                    try {
                        $weeeAttribute->getBackend()->afterLoad($product);
                    } catch (\Exception $e) {
                        // The attribute backend is invalid, not exporting anything is fine then.
                    }
                }
            }
        }
    }

    /**
     * @param StoreInterface $store
     * @param CatalogProduct $product
     * @return string
     */
    public function getCatalogProductFrontendUrl(StoreInterface $store, CatalogProduct $product)
    {
        $this->frontendUrlBuilder->setScope($store->getBaseStoreId());

        $requestPath = null;
        $urlDataObject = $product->getDataByKey('url_data_object');

        if ($urlDataObject instanceof DataObject) {
            $requestPath = trim((string) $urlDataObject->getData('url_rewrite'));
        }

        if (empty($requestPath)) {
            // Force the initialization of the request path, if possible.
            /** @see \Magento\Catalog\Model\Product\Url::getUrl() */
            $product->getProductUrl(false);
            $requestPath = trim((string) $product->getRequestPath());
        }

        $routeParameters = [
            '_nosid' => true,
            '_scope' => $store->getBaseStoreId(),
        ];

        if (!empty($requestPath)) {
            $routePath = '';
            $routeParameters['_direct'] = $requestPath;
        } else {
            $routePath = 'catalog/product/view';
            $routeParameters['id'] = $product->getId();
            $routeParameters['s'] = $product->getUrlKey();
        }

        return $this->frontendUrlBuilder->getUrl($routePath, $routeParameters);
    }

    /**
     * @return EavEntityType
     */
    private function getProductEavEntityType()
    {
        if (null === $this->productEavEntityType) {
            $this->productEavEntityType = $this->eavEntityTypeFactory->create();
            $this->productEavEntityType->loadByCode(CatalogProduct::ENTITY);
        }

        return $this->productEavEntityType;
    }

    /**
     * @param int $attributeSetId
     * @return string
     */
    public function getAttributeSetName($attributeSetId)
    {
        if (null === $this->attributeSetNames) {
            $this->attributeSetNames = $this->getProductEavEntityType()
                ->getAttributeSetCollection()
                ->toOptionHash();
        }

        return isset($this->attributeSetNames[$attributeSetId])
            ? trim((string) $this->attributeSetNames[$attributeSetId])
            : '';
    }

    /**
     * @param CatalogProduct $product
     * @param AbstractAttribute $weightAttribute
     * @return bool
     */
    private function hasProductWeightValue(CatalogProduct $product, AbstractAttribute $weightAttribute)
    {
        if ($weightAttribute->getAttributeCode() === 'weight') {
            return (
                ($product->getTypeId() !== ProductType::TYPE_VIRTUAL)
                && (($product->getTypeId() !== ProductType::TYPE_BUNDLE) || $product->getWeightType())
            );
        }

        $weight = $product->getData($weightAttribute->getAttributeCode());

        return !is_scalar($weight) || ('' !== trim((string) $weight));
    }

    public function getProductData(StoreInterface $store, RefreshableProduct $product)
    {
        $config = $this->getConfig();
        $catalogProduct = $product->getCatalogProduct();
        $productId = (int) $catalogProduct->getId();
        $productSku = $catalogProduct->getSku();
        $productTypeId = $catalogProduct->getTypeId();

        $data = [
            self::KEY_SKU => $config->shouldUseProductIdForSku($store) ? $productId : $productSku,
            self::KEY_NAME => $catalogProduct->getName(),
            self::KEY_URL => $this->getCatalogProductFrontendUrl($store, $catalogProduct),
        ];

        if ($attribute = $config->getBrandAttribute($store)) {
            $data[self::KEY_BRAND] = $this->getCatalogProductAttributeValue($store, $catalogProduct, $attribute);
        }

        if ($attribute = $config->getDescriptionAttribute($store)) {
            $data[self::KEY_DESCRIPTION] = $this->getCatalogProductAttributeValue($store, $catalogProduct, $attribute);
        }

        if ($attribute = $config->getShortDescriptionAttribute($store)) {
            $data[self::KEY_SHORT_DESCRIPTION] = $this->getCatalogProductAttributeValue(
                $store,
                $catalogProduct,
                $attribute
            );
        }

        if ($attribute = $config->getGtinAttribute($store)) {
            $data[self::KEY_GTIN] = $this->getCatalogProductAttributeValue($store, $catalogProduct, $attribute);
        }

        if ($attribute = $config->getWeightAttribute($store)) {
            if ($this->hasProductWeightValue($catalogProduct, $attribute)) {
                $data[self::KEY_WEIGHT] = (float) $this->getCatalogProductAttributeValue(
                    $store,
                    $catalogProduct,
                    $attribute
                );
            } elseif (ProductType::TYPE_BUNDLE === $productTypeId) {
                $data[self::KEY_DYNAMIC_WEIGHT] = true;
            }
        }

        foreach ($config->getAttributeMap($store) as $key => $attribute) {
            $value = $this->getCatalogProductAttributeValue($store, $catalogProduct, $attribute);
            $data[self::KEY_ATTRIBUTE_MAP][$key] = $value;
        }

        if (ProductType::TYPE_SIMPLE === $productTypeId) {
            foreach ($this->configurableAttributeSource->getAttributesByCode() as $code => $attribute) {
                $value = $this->getCatalogProductAttributeValue($store, $catalogProduct, $attribute);
                $data[self::KEY_CONFIGURABLE_ATTRIBUTES][$code] = $value;
            }
        }

        if ($config->shouldExportAttributeSetName($store)) {
            $data[self::KEY_ATTRIBUTE_SET] = $this->getAttributeSetName($catalogProduct->getAttributeSetId());
        }

        return $data;
    }

    public function adaptBundleProductData(
        StoreInterface $store,
        array $bundleData,
        array $childrenData,
        array $childrenQuantities
    ) {
        if (empty($bundleData[self::KEY_DYNAMIC_WEIGHT])) {
            return $bundleData;
        }

        $weight = 0.0;

        foreach ($childrenData as $key => $childData) {
            $bundledQuantity = max(1, $childrenQuantities[$key] ?? 0);
            $weight += ((float) ($childData[self::KEY_WEIGHT] ?? 0.0)) * $bundledQuantity;
        }

        $bundleData[self::KEY_WEIGHT] = $weight;

        return $bundleData;
    }

    public function exportBaseProductData(
        StoreInterface $store,
        array $productData,
        AbstractExportedProduct $exportedProduct
    ) {
        $config = $this->getConfig();

        if (isset($productData[self::KEY_SKU])) {
            $exportedProduct->setReference($productData[self::KEY_SKU]);
        }

        if (isset($productData[self::KEY_GTIN])) {
            $exportedProduct->setGtin($productData[self::KEY_GTIN]);
        }

        if (isset($productData[self::KEY_WEIGHT])) {
            if (is_callable([ $exportedProduct, 'setWeight' ])) {
                $exportedProduct->setWeight($productData[self::KEY_WEIGHT]);
            } else {
                $exportedProduct->setAttribute('weight', $productData[self::KEY_WEIGHT]);
            }
        }

        if (isset($productData[self::KEY_ATTRIBUTE_MAP])) {
            foreach ($config->getAttributeMap($store) as $key => $attribute) {
                if (isset($productData[self::KEY_ATTRIBUTE_MAP][$key])) {
                    $value = $productData[self::KEY_ATTRIBUTE_MAP][$key];

                    if (is_array($value)) {
                        foreach ($value as $subKey => $subValue) {
                            $exportedProduct->setAttribute($key . '-' . $subKey, $subValue);
                        }
                    } else {
                        // Backwards compatibility: keep exporting the non-first-class "weight" attribute as "weight",
                        // as long as it seems necessary to prevent breaking the existing SF configuration.
                        if (('weight' === $key) && !$config->getWeightAttribute($store)) {
                            $exportedProduct->setAttribute('weight', $value);
                        }

                        if (in_array($key, Type::RESERVED_ATTRIBUTE_CODES, true)) {
                            $key .= $this->reservedAttributeCodeSuffix;
                        }

                        $exportedProduct->setAttribute($key, $value);
                    }
                }
            }
        }

        if (isset($productData[self::KEY_ATTRIBUTE_SET])) {
            $exportedProduct->setAttribute(self::KEY_ATTRIBUTE_SET, $productData[self::KEY_ATTRIBUTE_SET]);
        }
    }

    public function exportMainProductData(
        StoreInterface $store,
        array $productData,
        ExportedProduct $exportedProduct
    ) {
        if (isset($productData[self::KEY_NAME])) {
            $exportedProduct->setName($productData[self::KEY_NAME]);
        }

        if (isset($productData[self::KEY_BRAND])) {
            $exportedProduct->setBrand($productData[self::KEY_BRAND]);
        }

        $description = (string) ($productData[self::KEY_DESCRIPTION] ?? '');
        $shortDescription = (string) ($productData[self::KEY_SHORT_DESCRIPTION] ?? '');

        if (('' !== $description) || ('' !== $shortDescription)) {
            $exportedProduct->setDescription($description, $shortDescription);
        }

        if (isset($productData[self::KEY_URL])) {
            $exportedProduct->setLink($productData[self::KEY_URL]);
            $exportedProduct->setAttribute('url', $productData[self::KEY_URL]);
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
                        $productData[self::KEY_CONFIGURABLE_ATTRIBUTES][$attributeCode]
                    );
                }
            }
        }

        if (
            $this->getConfig()->shouldExportVariationUrls($store)
            && isset($productData[self::KEY_URL])
        ) {
            $exportedVariation->setLink($productData[self::KEY_URL]);
            $exportedVariation->setAttribute('url', $productData[self::KEY_URL]);
        }
    }

    public function describeProductData(StoreInterface $store, array $productData)
    {
        $data = $this->describeRawProductData(
            [
                self::KEY_SKU => __('SKU'),
                self::KEY_NAME => __('Name'),
                self::KEY_GTIN => __('GTIN'),
                self::KEY_ATTRIBUTE_SET => __('Attribute Set'),
                self::KEY_BRAND => __('Brand'),
                self::KEY_URL => __('URL'),
                self::KEY_DESCRIPTION => __('Description'),
                self::KEY_SHORT_DESCRIPTION => __('Short Description'),
                self::KEY_WEIGHT => __('Weight'),
                self::KEY_DYNAMIC_WEIGHT => __('Dynamic Weight'),
            ],
            $productData
        );

        if (isset($productData[self::KEY_ATTRIBUTE_MAP])) {
            foreach ($this->getConfig()->getAttributeMap($store) as $key => $attribute) {
                if (isset($productData[self::KEY_ATTRIBUTE_MAP][$key])) {
                    $data[] = $this->createLabelledValue(
                        __('Attribute %1', $key),
                        $productData[self::KEY_ATTRIBUTE_MAP][$key]
                    );
                }
            }
        }

        return $data;
    }
}
