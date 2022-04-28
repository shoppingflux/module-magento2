<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section;

use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Store\Model\StoreManagerInterface;
use ShoppingFeed\Feed\Product\AbstractProduct as AbstractExportedProduct;
use ShoppingFeed\Feed\Product\Product as ExportedProduct;
use ShoppingFeed\Feed\Product\ProductVariation as ExportedVariation;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value\RendererPoolInterface as AttributeRendererPoolInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\ConfigInterface as SectionConfig;
use ShoppingFeed\Manager\Model\LabelledValue;
use ShoppingFeed\Manager\Model\LabelledValueFactory;

abstract class AbstractAdapter implements AdapterInterface
{
    /**
     * @var AbstractType|null
     */
    private $type = null;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var LabelledValueFactory
     */
    private $labelledValueFactory;

    /**
     * @var AttributeRendererPoolInterface
     */
    private $attributeRendererPool;

    /**
     * @param StoreManagerInterface $storeManager
     * @param LabelledValueFactory $labelledValueFactory
     * @param AttributeRendererPoolInterface $attributeRendererPool
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        LabelledValueFactory $labelledValueFactory,
        AttributeRendererPoolInterface $attributeRendererPool
    ) {
        $this->storeManager = $storeManager;
        $this->labelledValueFactory = $labelledValueFactory;
        $this->attributeRendererPool = $attributeRendererPool;
    }

    public function setType(AbstractType $type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return SectionConfig
     */
    public function getConfig()
    {
        return $this->type->getConfig();
    }

    public function requiresLoadedProduct(StoreInterface $store)
    {
        return false;
    }

    public function prepareLoadableProductCollection(StoreInterface $store, ProductCollection $productCollection)
    {
    }

    public function prepareLoadedProductCollection(StoreInterface $store, ProductCollection $productCollection)
    {
    }

    public function adaptRetainedProductData(StoreInterface $store, array $productData)
    {
        return $productData;
    }

    public function adaptNonExportableProductData(StoreInterface $store, array $productData)
    {
        return $productData;
    }

    public function adaptParentProductData(StoreInterface $store, array $parentData, array $childrenData)
    {
        return $parentData;
    }

    public function adaptBundleProductData(
        StoreInterface $store,
        array $bundleData,
        array $childrenData,
        array $childrenQuantities
    ) {
        return $bundleData;
    }

    public function adaptChildProductData(StoreInterface $store, array $productData)
    {
        return $productData;
    }

    public function exportBaseProductData(
        StoreInterface $store,
        array $productData,
        AbstractExportedProduct $exportedProduct
    ) {
    }

    public function exportMainProductData(
        StoreInterface $store,
        array $productData,
        ExportedProduct $exportedProduct
    ) {
    }

    public function exportVariationProductData(
        StoreInterface $store,
        array $productData,
        array $configurableAttributeCodes,
        ExportedVariation $exportedVariation
    ) {
    }

    /**
     * @param string $label
     * @param array|string $value
     * @return LabelledValue
     */
    protected function createLabelledValue($label, $value)
    {
        return $this->labelledValueFactory->create(
            [
                'label' => trim((string) $label),
                'value' => is_array($value) ? json_encode($value) : (string) $value,
            ]
        );
    }

    /**
     * @param string[] $keyLabels
     * @param array $productData
     * @return LabelledValue[]
     */
    protected function describeRawProductData(array $keyLabels, array $productData)
    {
        $data = [];

        foreach ($keyLabels as $key => $label) {
            if (isset($productData[$key])) {
                $data[] = $this->createLabelledValue($label, $productData[$key]);
            }
        }

        return $data;
    }

    /**
     * @param StoreInterface $store
     * @return int
     */
    public function getStoreBaseWebsiteId(StoreInterface $store)
    {
        return $this->storeManager->getStore($store->getBaseStoreId())->getWebsiteId();
    }

    /**
     * @param StoreInterface $store
     * @param CatalogProduct $product
     * @param AbstractAttribute $attribute
     * @return string|null
     */
    protected function getCatalogProductAttributeValue(
        StoreInterface $store,
        CatalogProduct $product,
        AbstractAttribute $attribute
    ) {
        $attributeValue = null;

        foreach ($this->attributeRendererPool->getSortedRenderers() as $attributeRenderer) {
            if ($attributeRenderer->isAppliableToAttribute($attribute)) {
                $attributeValue = $attributeRenderer->getProductAttributeValue($store, $product, $attribute);
                break;
            }
        }

        return $attributeValue;
    }
}
