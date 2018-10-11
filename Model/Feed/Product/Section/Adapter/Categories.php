<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Adapter;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Store\Model\StoreManagerInterface;
use ShoppingFeed\Feed\Product\Product as ExportedProduct;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value\RendererPoolInterface as AttributeRendererPoolInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Category\SelectorInterface as CategorySelectorInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractAdapter;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Config\CategoriesInterface as ConfigInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Type\Categories as Type;
use ShoppingFeed\Manager\Model\Feed\RefreshableProduct;

/**
 * @method ConfigInterface getConfig()
 */
class Categories extends AbstractAdapter implements CategoriesInterface
{
    const CATEGORY_NAME_SEPARATOR = ' > ';

    const KEY_CATEGORY_NAME = 'category_name';
    const KEY_CATEGORY_URL = 'category_url';

    /**
     * @var CategorySelectorInterface
     */
    private $productCategorySelector;

    /**
     * @param StoreManagerInterface $storeManager
     * @param AttributeRendererPoolInterface $attributeRendererPool
     * @param CategorySelectorInterface $productCategorySelector
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        AttributeRendererPoolInterface $attributeRendererPool,
        CategorySelectorInterface $productCategorySelector
    ) {
        $this->productCategorySelector = $productCategorySelector;
        parent::__construct($storeManager, $attributeRendererPool);
    }

    public function getSectionType()
    {
        return Type::CODE;
    }

    public function prepareLoadedProductCollection(StoreInterface $store, ProductCollection $productCollection)
    {
        $productCollection->addCategoryIds();
    }

    public function getProductData(StoreInterface $store, RefreshableProduct $product)
    {
        $data = [];
        $config = $this->getConfig();

        $categoryPath = $this->productCategorySelector->getCatalogProductCategoryPath(
            $product->getCatalogProduct(),
            $store,
            $product->getFeedProduct()->getSelectedCategoryId(),
            $config->getCategorySelectionIds($store),
            $config->getCategorySelectionMode($store),
            $config->getMaximumCategoryLevel($store),
            $config->getLevelWeightMultiplier($store),
            $config->shouldUseParentCategories($store),
            $config->getIncludableParentCount($store),
            $config->getMinimumParentLevel($store),
            $config->getParentWeightMultiplier($store)
        );

        if (is_array($categoryPath) && !empty($categoryPath)) {
            $mainCategory = reset($categoryPath);
            $data[self::KEY_CATEGORY_URL] = $mainCategory->getUrl();
            $pathNames = [];

            foreach ($categoryPath as $category) {
                $pathNames[] = $category->getName();
            }

            $data[self::KEY_CATEGORY_NAME] = implode(self::CATEGORY_NAME_SEPARATOR, array_reverse($pathNames));
        }

        return $data;
    }

    public function exportMainProductData(
        StoreInterface $store,
        array $productData,
        ExportedProduct $exportedProduct
    ) {
        if (isset($productData[self::KEY_CATEGORY_NAME])) {
            $exportedProduct->setCategory(
                $productData[self::KEY_CATEGORY_NAME],
                $productData[self::KEY_CATEGORY_URL] ?? ''
            );
        }
    }
}
