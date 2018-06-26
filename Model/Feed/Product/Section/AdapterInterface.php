<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section;

use ShoppingFeed\Feed\Product\AbstractProduct as AbstractExportedProduct;
use ShoppingFeed\Feed\Product\Product as ExportedProduct;
use ShoppingFeed\Feed\Product\ProductVariation as ExportedVariation;
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
     * @return array
     */
    public function adaptParentProductData(StoreInterface $store, array $parentData, array $childrenData);

    /**
     * @param StoreInterface $store
     * @param array $productData
     * @return array
     */
    public function adaptChildProductData(StoreInterface $store, array $productData);

    /**
     * @param StoreInterface $store
     * @param array $productData
     * @param AbstractExportedProduct $exportedProduct
     */
    public function exportBaseProductData(
        StoreInterface $store,
        array $productData,
        AbstractExportedProduct $exportedProduct
    );

    /**
     * @param StoreInterface $store
     * @param array $productData
     * @param ExportedProduct $exportedProduct
     */
    public function exportMainProductData(
        StoreInterface $store,
        array $productData,
        ExportedProduct $exportedProduct
    );

    /**
     * @param StoreInterface $store
     * @param array $productData
     * @param array $configurableAttributeCodes
     * @param ExportedVariation $exportedVariation
     */
    public function exportVariationProductData(
        StoreInterface $store,
        array $productData,
        array $configurableAttributeCodes,
        ExportedVariation $exportedVariation
    );
}
