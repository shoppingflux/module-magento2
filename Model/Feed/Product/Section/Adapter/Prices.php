<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Adapter;

use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Catalog\Model\ResourceModel\Product as CatalogProductResource;
use Magento\Catalog\Model\ResourceModel\ProductFactory as CatalogProductResourceFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Api\TaxCalculationInterface\Proxy as TaxCalculationProxy;
use Magento\Tax\Model\Config as TaxConfig;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value\RendererPoolInterface as AttributeRendererPoolInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractAdapter;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Config\PricesInterface as ConfigInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Type\Prices as Type;
use ShoppingFeed\Manager\Model\Feed\RefreshableProduct;


/**
 * @method ConfigInterface getConfig()
 */
class Prices extends AbstractAdapter implements PricesInterface
{
    const KEY_BASE_PRICE = 'base_price';
    const KEY_SPECIAL_PRICE = 'special_price';
    const KEY_FINAL_PRICE = 'final_price';
    const KEY_SPECIAL_PRICE_FROM_DATE = 'special_price_from_date';
    const KEY_SPECIAL_PRICE_TO_DATE = 'special_price_to_date';
    const KEY_DISCOUNT_AMOUNT = 'discount_amount';
    const KEY_DISCOUNT_PERCENTAGE = 'discount_percentage';

    /**
     * @var CatalogProductResource
     */
    private $catalogProductResource;

    /**
     * @var TaxCalculationProxy
     */
    private $taxCalculatorProxy;

    public function __construct(
        StoreManagerInterface $storeManager,
        AttributeRendererPoolInterface $attributeRendererPool,
        CatalogProductResourceFactory $catalogProductResourceFactory,
        TaxCalculationProxy $taxCalculatorProxy
    ) {
        $this->catalogProductResource = $catalogProductResourceFactory->create();
        $this->taxCalculatorProxy = $taxCalculatorProxy;
        parent::__construct($storeManager, $attributeRendererPool);
    }

    public function getSectionType()
    {
        return Type::CODE;
    }

    /**
     * @param float $price
     * @param float|false $taxRate
     * @return float
     */
    private function applyTaxRateOnPrice($price, $taxRate)
    {
        return (false !== $taxRate) ? ($price + $price * $taxRate / 100) : $price;
    }

    /**
     * @param CatalogProduct $catalogProduct
     * @param string $attributeCode
     * @return string
     */
    private function getDateValue(CatalogProduct $catalogProduct, $attributeCode)
    {
        if ($attribute = $this->catalogProductResource->getAttribute($attributeCode)) {
            return !empty($dateValue = $this->getCatalogProductAttributeValue($catalogProduct, $attribute))
                ? $dateValue
                : (string) $catalogProduct->getData($attributeCode);
        }

        return '';
    }

    public function getProductData(StoreInterface $store, RefreshableProduct $product)
    {
        $catalogProduct = $product->getCatalogProduct();
        $taxRate = false;
        $isPriceIncludingTax = (bool) $store->getScopeConfigValue(TaxConfig::CONFIG_XML_PATH_PRICE_INCLUDES_TAX);

        if (!$isPriceIncludingTax) {
            if ($taxClassId = (int) $catalogProduct->getData('tax_class_id')) {
                $taxRate = $this->taxCalculatorProxy->getCalculatedRate($taxClassId, null, $store->getBaseStoreId());
            }
        }

        $basePrice = $this->applyTaxRateOnPrice($catalogProduct->getPrice(), $taxRate);
        $specialPrice = $this->applyTaxRateOnPrice($catalogProduct->getSpecialPrice(), $taxRate);
        $finalPrice = $this->applyTaxRateOnPrice($catalogProduct->getFinalPrice(), $taxRate);

        if ($basePrice > $finalPrice) {
            $discountAmount = round($basePrice - $finalPrice, 2);
            $discountPercentage = round($finalPrice * 100 / $basePrice, 2);
        } else {
            $discountAmount = 0;
            $discountPercentage = 0;
        }

        return [
            self::KEY_BASE_PRICE => round($basePrice, 2),
            self::KEY_SPECIAL_PRICE => ($specialPrice > 0) ? round($specialPrice, 2) : '',
            self::KEY_FINAL_PRICE => round($finalPrice, 2),
            self::KEY_DISCOUNT_AMOUNT => $discountAmount,
            self::KEY_DISCOUNT_PERCENTAGE => $discountPercentage,
            self::KEY_SPECIAL_PRICE_FROM_DATE => $this->getDateValue($catalogProduct, 'special_from_date'),
            self::KEY_SPECIAL_PRICE_TO_DATE => $this->getDateValue($catalogProduct, 'special_to_date'),
        ];
    }
}
