<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section;

use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product\AdapterInterface as BaseInterface;
use ShoppingFeed\Manager\Model\Feed\RefreshableProduct;


interface AdapterInterface extends BaseInterface
{
    /**
     * @param AbstractType $type
     * @return $this
     */
    public function setType(AbstractType $type);

    /**
     * @param StoreInterface $store
     * @param RefreshableProduct $product
     * @return array
     */
    public function getProductData(StoreInterface $store, RefreshableProduct $product);

    /**
     * @param StoreInterface $store
     * @param array $productData
     * @return array
     */
    public function adaptRetainedProductData(StoreInterface $store, array $productData);

    /**
     * @param StoreInterface $store
     * @param array $parentData
     * @param array[] $childrenData
     * @return mixed
     */
    public function adaptParentProductData(StoreInterface $store, array $parentData, array $childrenData);

    /**
     * @param StoreInterface $store
     * @param array $productData
     * @return array
     */
    public function adaptChildProductData(StoreInterface $store, array $productData);
}
