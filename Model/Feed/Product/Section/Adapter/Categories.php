<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Adapter;

use Magento\Store\Model\StoreManagerInterface;
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
    const BREADCRUMBS_SEPARATOR = ' > '; // @todo configuration field?

    const KEY_BREADCRUMBS = 'category_breadcrumbs';
    const KEY_MAIN_CATEGORY_ID = 'category_id';
    const KEY_MAIN_CATEGORY_NAME = 'category_main';
    const KEY_MAIN_CATEGORY_URL = 'category_main_url';
    const BASE_KEY_SUB_CATEGORY_ID = 'category_sub_%d_id';
    const BASE_KEY_SUB_CATEGORY_NAME = 'category_sub_%d_main';
    const BASE_KEY_SUB_CATEGORY_URL = 'category_sub_%d_url';

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

        if (is_array($categoryPath)) {
            $index = 0;
            $breadcrumbs = [];

            foreach ($categoryPath as $category) {
                if ($index++ === 0) {
                    $idKey = self::KEY_MAIN_CATEGORY_ID;
                    $nameKey = self::KEY_MAIN_CATEGORY_NAME;
                    $urlKey = self::KEY_MAIN_CATEGORY_URL;
                } else {
                    $idKey = sprintf(self::BASE_KEY_SUB_CATEGORY_ID, $index);
                    $nameKey = sprintf(self::BASE_KEY_SUB_CATEGORY_NAME, $index);
                    $urlKey = sprintf(self::BASE_KEY_SUB_CATEGORY_URL, $index);
                }

                $data[$idKey] = $category->getId();
                $data[$nameKey] = $category->getName();
                $data[$urlKey] = $category->getUrl();
                $breadcrumbs[] = $category->getName();
            }

            $data[self::KEY_BREADCRUMBS] = implode(self::BREADCRUMBS_SEPARATOR, array_reverse($breadcrumbs));
        }

        return $data;
    }
}
