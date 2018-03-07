<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section;

use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Store\Model\StoreManagerInterface;
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
        // @todo the adapter should only return whether or not it always *itself* requires a loaded product
        // @todo then the user-defined configuration value should be checked on top of it by whatever requires it
        // @todo this would eg allow for displaying a specific notice to the user
        return true; // $this->getConfig()->shouldForceProductLoadForRefresh($store);
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
