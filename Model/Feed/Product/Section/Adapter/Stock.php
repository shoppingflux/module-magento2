<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Adapter;

use Magento\Store\Model\StoreManagerInterface;
use ShoppingFeed\Feed\Product\AbstractProduct as AbstractExportedProduct;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value\RendererPoolInterface as AttributeRendererPoolInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractAdapter;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Config\StockInterface as ConfigInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Type\Stock as Type;
use ShoppingFeed\Manager\Model\Feed\Product\Stock\QtyResolverInterface;
use ShoppingFeed\Manager\Model\Feed\RefreshableProduct;
use ShoppingFeed\Manager\Model\LabelledValueFactory;

/**
 * @method ConfigInterface getConfig()
 */
class Stock extends AbstractAdapter implements StockInterface
{
    const KEY_QUANTITY = 'qty';
    const KEY_IS_IN_STOCK = 'is_in_stock';

    /**
     * @var QtyResolverInterface
     */
    protected $qtyResolver;

    /**
     * @param StoreManagerInterface $storeManager
     * @param LabelledValueFactory $labelledValueFactory
     * @param AttributeRendererPoolInterface $attributeRendererPool
     * @param QtyResolverInterface $qtyResolver
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        LabelledValueFactory $labelledValueFactory,
        AttributeRendererPoolInterface $attributeRendererPool,
        QtyResolverInterface $qtyResolver
    ) {
        $this->qtyResolver = $qtyResolver;
        parent::__construct($storeManager, $labelledValueFactory, $attributeRendererPool);
    }

    public function getSectionType()
    {
        return Type::CODE;
    }

    public function requiresLoadedProduct(StoreInterface $store)
    {
        return $this->getConfig()->shouldForceZeroQuantityForNonSalable($store);
    }

    public function getProductData(StoreInterface $store, RefreshableProduct $product)
    {
        $config = $this->getConfig();
        $quantity = $config->getDefaultQuantity($store);
        $isInStock = ($quantity > 0);

        if (
            $config->shouldForceZeroQuantityForNonSalable($store)
            && !$product->getCatalogProduct()->isSalable()
        ) {
            $quantity = 0;
            $isInStock = false;
        } elseif ($config->shouldUseActualStockState($store)) {
            $stockQuantity = $this->qtyResolver->getCatalogProductQuantity(
                $product->getCatalogProduct(),
                $store,
                $config->getMsiQuantityType($store)
            );

            $isInStock = $this->qtyResolver->isCatalogProductInStock(
                $product->getCatalogProduct(),
                $store,
                $config->getMsiQuantityType($store)
            );

            if (null !== $stockQuantity) {
                $quantity = $stockQuantity;
            }
        }

        return [
            self::KEY_QUANTITY => (int) floor($quantity),
            self::KEY_IS_IN_STOCK => $isInStock ? 1 : 0,
        ];
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

        if (isset($productData[self::KEY_IS_IN_STOCK])) {
            $exportedProduct->setAttribute(self::KEY_IS_IN_STOCK, $productData[self::KEY_IS_IN_STOCK]);
        }
    }

    public function describeProductData(StoreInterface $store, array $productData)
    {
        return $this->describeRawProductData(
            [
                self::KEY_QUANTITY => __('Quantity'),
                self::KEY_IS_IN_STOCK => __('Is In Stock'),
            ],
            $productData
        );
    }
}
