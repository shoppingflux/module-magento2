<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Adapter;

use Magento\Store\Model\StoreManagerInterface;
use ShoppingFeed\Feed\Product\AbstractProduct as AbstractExportedProduct;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value\RendererPoolInterface as AttributeRendererPoolInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractAdapter;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Config\StockInterface as ConfigInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Type\Stock as Type;
use ShoppingFeed\Manager\Model\Feed\Product\Stock\QtyResolver;
use ShoppingFeed\Manager\Model\Feed\RefreshableProduct;
use ShoppingFeed\Manager\Model\LabelledValueFactory;

/**
 * @method ConfigInterface getConfig()
 */
class Stock extends AbstractAdapter implements StockInterface
{
    const KEY_QUANTITY = 'qty';

    /**
     * @var QtyResolver
     */
    protected $qtyResolver;

    /**
     * @param StoreManagerInterface $storeManager
     * @param LabelledValueFactory $labelledValueFactory
     * @param AttributeRendererPoolInterface $attributeRendererPool
     * @param QtyResolver $qtyResolver
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        LabelledValueFactory $labelledValueFactory,
        AttributeRendererPoolInterface $attributeRendererPool,
        QtyResolver $qtyResolver
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
        $quantity = $this->getConfig()->getDefaultQuantity($store);

        if ($this->getConfig()->shouldForceZeroQuantityForNonSalable($store)
            && !$product->getCatalogProduct()->isSalable()
        ) {
            $quantity = 0;
        } elseif ($this->getConfig()->shouldUseActualStockState($store)) {
            $stockQuantity = $this->qtyResolver->getCatalogProductQuantity($product->getCatalogProduct(), $store);

            if (null !== $stockQuantity) {
                $quantity = $stockQuantity;
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

    public function describeProductData(StoreInterface $store, array $productData)
    {
        return $this->describeRawProductData([ self::KEY_QUANTITY => __('Quantity') ], $productData);
    }
}
