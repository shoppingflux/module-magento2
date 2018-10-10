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
     * @var AttributeRendererPoolInterface
     */
    private $attributeRendererPool;

    /**
     * @param StoreManagerInterface $storeManager
     * @param AttributeRendererPoolInterface $attributeRendererPool
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        AttributeRendererPoolInterface $attributeRendererPool
    ) {
        $this->storeManager = $storeManager;
        $this->attributeRendererPool = $attributeRendererPool;
    }

    final public function setType(AbstractType $type)
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

    public function adaptParentProductData(StoreInterface $store, array $parentData, array $childrenData)
    {
        return $parentData;
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
     * @param StoreInterface $store
     * @return int
     */
    public function getStoreBaseWebsiteId(StoreInterface $store)
    {
        return $this->storeManager->getStore($store->getBaseStoreId())->getWebsiteId();
    }

    /**
     * @param CatalogProduct $product
     * @param AbstractAttribute $attribute
     * @return string|null
     */
    protected function getCatalogProductAttributeValue(CatalogProduct $product, AbstractAttribute $attribute)
    {
        $attributeValue = null;

        foreach ($this->attributeRendererPool->getSortedRenderers() as $attributeRenderer) {
            if ($attributeRenderer->isAppliableToAttribute($attribute)) {
                $attributeValue = $attributeRenderer->getProductAttributeValue($product, $attribute);
                break;
            }
        }

        return $attributeValue;
    }
}
