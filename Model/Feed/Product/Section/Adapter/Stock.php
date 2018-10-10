<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Adapter;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Model\Stock\Item as StockItem;
use Magento\Store\Model\StoreManagerInterface;
use ShoppingFeed\Feed\Product\AbstractProduct as AbstractExportedProduct;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value\RendererPoolInterface as AttributeRendererPoolInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractAdapter;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Config\StockInterface as ConfigInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Type\Stock as Type;
use ShoppingFeed\Manager\Model\Feed\RefreshableProduct;

/**
 * @method ConfigInterface getConfig()
 */
class Stock extends AbstractAdapter implements StockInterface
{
    const KEY_QUANTITY = 'qty';

    /**
     * @var StockRegistryInterface $stockRegistry
     */
    protected $stockRegistry;

    /**
     * @param StoreManagerInterface $storeManager
     * @param AttributeRendererPoolInterface $attributeRendererPool
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        AttributeRendererPoolInterface $attributeRendererPool,
        StockRegistryInterface $stockRegistry
    ) {
        $this->stockRegistry = $stockRegistry;
        parent::__construct($storeManager, $attributeRendererPool);
    }

    public function getSectionType()
    {
        return Type::CODE;
    }

    public function getProductData(StoreInterface $store, RefreshableProduct $product)
    {
        $quantity = $this->getConfig()->getDefaultQuantity($store);

        if ($this->getConfig()->shouldUseActualStockState($store)) {
            $stockItem = $this->stockRegistry->getStockItem(
                $product->getCatalogProduct()->getId(),
                $this->getStoreBaseWebsiteId($store)
            );

            if ($stockItem instanceof StockItem) {
                // Ensure that the right system configuration values will be used.
                $stockItem->setStoreId($store->getBaseStoreId());
            }

            if ($stockItem->getManageStock()) {
                $quantity = $stockItem->getQty();
            }
        }

        return [ self::KEY_QUANTITY => (int) floor($quantity) ];
    }

    public function adaptRetainedProductData(StoreInterface $store, array $productData)
    {
        if (isset($productData[self::KEY_QUANTITY])) {
            $productData[self::KEY_QUANTITY] = 0;
        }

        return $productData;
    }

    public function adaptParentProductData(StoreInterface $store, array $parentData, array $childrenData)
    {
        if (isset($parentData[self::KEY_QUANTITY])) {
            unset($parentData[self::KEY_QUANTITY]);
        }

        return $parentData;
    }

    public function exportBaseProductData(
        StoreInterface $store,
        array $productData,
        AbstractExportedProduct $exportedProduct
    ) {
        if (isset($productData[self::KEY_QUANTITY])) {
            $exportedProduct->setQuantity($productData[self::KEY_QUANTITY]);
        }
    }
}
