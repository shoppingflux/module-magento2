<?php

namespace ShoppingFeed\Manager\Plugin\Bundle\Product;

use Magento\Bundle\Model\Product\Price as BundlePriceModel;
use Magento\Catalog\Model\Product;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as SalesOrderImporterInterface;

class PricePlugin
{
    const KEY_ORIGINAL_PRICE_TYPE = '_sfm_original_price_type_';
    const KEY_SKIP_PRICE_TYPE_OVERRIDE = '_sfm_skip_price_type_override_';

    /**
     * @var SalesOrderImporterInterface
     */
    private $salesOrderImporter;

    /**
     * @var bool
     */
    private $shouldForceFixedPriceType;

    /**
     * @param SalesOrderImporterInterface $salesOrderImporter
     * @param bool $shouldForceFixedPriceType
     */
    public function __construct(SalesOrderImporterInterface $salesOrderImporter, $shouldForceFixedPriceType = false)
    {
        $this->salesOrderImporter = $salesOrderImporter;
        $this->shouldForceFixedPriceType = $shouldForceFixedPriceType;
    }

    /**
     * @param Product $product
     */
    private function updateProductPriceType($product)
    {
        if (
            $this->shouldForceFixedPriceType
            && $this->salesOrderImporter->isImportRunning()
            && !$product->getData(self::KEY_SKIP_PRICE_TYPE_OVERRIDE)
        ) {
            if (!$product->hasData(self::KEY_ORIGINAL_PRICE_TYPE)) {
                $product->setData(self::KEY_ORIGINAL_PRICE_TYPE, $product->getPriceType());
            }

            $product->setPriceType(BundlePriceModel::PRICE_TYPE_FIXED);
        }
    }

    /**
     * @param BundlePriceModel $subject
     * @param Product $product
     */
    public function beforeGetPrice(BundlePriceModel $subject, $product)
    {
        $this->updateProductPriceType($product);
    }

    /**
     * @param BundlePriceModel $subject
     * @param Product $product
     * @param string|null $which
     * @param bool|null $includeTax
     * @param bool $takeTierPrice
     */
    public function beforeGetTotalPrices(
        BundlePriceModel $subject,
        $product,
        $which = null,
        $includeTax = null,
        $takeTierPrice = true
    ) {
        $this->updateProductPriceType($product);
    }

    /**
     * @param BundlePriceModel $subject
     * @param Product $bundleProduct
     * @param Product $selectionProduct
     * @param float $bundleQty
     * @param float $selectionQty
     * @param bool $multiplyQty
     * @param bool $takeTierPrice
     */
    public function beforeGetSelectionFinalTotalPrice(
        BundlePriceModel $subject,
        $bundleProduct,
        $selectionProduct,
        $bundleQty,
        $selectionQty,
        $multiplyQty = true,
        $takeTierPrice = true
    ) {
        $this->updateProductPriceType($bundleProduct);
    }
}
