<?php

namespace ShoppingFeed\Manager\Model\Feed\Product;

use Magento\Catalog\Model\Product as Product;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

class MptDetector
{
    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var bool[]
     */
    private $productResults = [];

    /**
     * @param ProductCollectionFactory $productCollectionFactory
     */
    public function __construct(ProductCollectionFactory $productCollectionFactory)
    {
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * @return bool
     */
    public function isCompatibleEnvironment()
    {
        return PHP_SAPI === 'cli';
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function isMagentoPerformanceToolkitFakeProduct(Product $product)
    {
        if (!$this->isCompatibleEnvironment()) {
            return false;
        }

        $productId = (int) $product->getId();

        if ($productId && isset($this->productResults[$productId])) {
            return $this->productResults[$productId];
        }

        $this->productResults[$productId] = (
            substr($product->getSku(), 0, 12) === 'template_sku'
            && substr($product->getName(), 0, 13) === 'template name'
        );

        return $this->productResults[$productId];
    }

    /**
     * @param int $productId
     * @return bool
     */
    public function isMagentoPerformanceToolkitFakeProductId($productId)
    {
        return !empty($this->getMagentoPerformanceToolkitFakeProductIds([ $productId ]));
    }

    /**
     * @param int[] $productIds
     * @return int[]
     */
    public function getMagentoPerformanceToolkitFakeProductIds(array $productIds)
    {
        if (!$this->isCompatibleEnvironment()) {
            return [];
        }

        $productIds = array_filter(array_unique($productIds));
        $fakeProductIds = [];

        foreach ($productIds as $key => $productId) {
            if (isset($this->productResults[$productId])) {
                unset($productIds[$key]);

                if ($this->productResults[$productId]) {
                    $fakeProductIds[] = $productId;
                }
            }
        }

        if (!empty($productIds)) {
            $productCollection = $this->productCollectionFactory->create();

            $productCollection->addIdFilter($productIds);
            $productCollection->addAttributeToSelect([ 'sku', 'name' ]);

            /** @var Product $product */
            foreach ($productCollection as $product) {
                if ($this->isMagentoPerformanceToolkitFakeProduct($product)) {
                    $fakeProductIds[] = (int) $product->getId();
                }
            }
        }

        return $fakeProductIds;
    }
}
