<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Adapter;

use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Catalog\Model\Product\Type as CatalogProductType;
use Magento\Catalog\Model\ResourceModel\Product as CatalogProductResource;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\ProductFactory as CatalogProductResourceFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableProductType;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Api\TaxCalculationInterface as TaxCalculationInterface;
use Magento\Tax\Model\Config as TaxConfig;
use ShoppingFeed\Feed\Product\AbstractProduct as AbstractExportedProduct;
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

    /**
     * @var CatalogProductResourceFactory
     */
    private $catalogProductResourceFactory;

    /**
     * @var CatalogProductResource|null
     */
    private $catalogProductResource = null;

    /**
     * @var ConfigurableProductType
     */
    private $configurableProductType;

    /**
     * @var TaxCalculationInterface
     */
    private $taxCalculator;

    /**
     * @param StoreManagerInterface $storeManager
     * @param AttributeRendererPoolInterface $attributeRendererPool
     * @param CatalogProductResourceFactory $catalogProductResourceFactory
     * @param ConfigurableProductType $configurableProductType
     * @param TaxCalculationInterface $taxCalculator
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        AttributeRendererPoolInterface $attributeRendererPool,
        CatalogProductResourceFactory $catalogProductResourceFactory,
        ConfigurableProductType $configurableProductType,
        TaxCalculationInterface $taxCalculator
    ) {
        $this->catalogProductResourceFactory = $catalogProductResourceFactory;
        $this->configurableProductType = $configurableProductType;
        $this->taxCalculator = $taxCalculator;
        parent::__construct($storeManager, $attributeRendererPool);
    }

    public function getSectionType()
    {
        return Type::CODE;
    }

    public function prepareLoadableProductCollection(StoreInterface $store, ProductCollection $productCollection)
    {
        $productCollection->addPriceData(
            $this->getConfig()->getCustomerGroupId($store),
            $store->getBaseStore()->getWebsiteId()
        );

        $productCollection->addMinimalPrice();
        $productCollection->addFinalPrice();
        $productCollection->addTaxPercents();
    }

    public function prepareLoadedProductCollection(StoreInterface $store, ProductCollection $productCollection)
    {
        $productCollection->addTierPriceData();
    }

    /**
     * @return CatalogProductResource
     */
    private function getCatalogProductResource()
    {
        if (null === $this->catalogProductResource) {
            $this->catalogProductResource = $this->catalogProductResourceFactory->create();
        }

        return $this->catalogProductResource;
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
     * @param StoreInterface $store
     * @param CatalogProduct $catalogProduct
     * @param string $attributeCode
     * @return string
     */
    private function getDateValue(StoreInterface $store, CatalogProduct $catalogProduct, $attributeCode)
    {
        if ($attribute = $this->getCatalogProductResource()->getAttribute($attributeCode)) {
            return !empty($dateValue = $this->getCatalogProductAttributeValue($store, $catalogProduct, $attribute))
                ? $dateValue
                : (string) $catalogProduct->getData($attributeCode);
        }

        return '';
    }

    /**
     * @param CatalogProduct $product
     * @param StoreInterface $store
     * @return array
     */
    private function getSimpleProductPriceData(CatalogProduct $product, StoreInterface $store)
    {
        $taxRate = false;
        $isPriceIncludingTax = (bool) $store->getScopeConfigValue(TaxConfig::CONFIG_XML_PATH_PRICE_INCLUDES_TAX);

        if (!$isPriceIncludingTax) {
            if ($taxClassId = (int) $product->getData('tax_class_id')) {
                $taxRate = $this->taxCalculator->getCalculatedRate($taxClassId, null, $store->getBaseStoreId());
            }
        }

        $originalCustomerGroupId = $product->getCustomerGroupId();
        $originalWebsiteId = $product->getWebsiteId();
        $product->setCustomerGroupId($this->getConfig()->getCustomerGroupId($store));
        $product->setWebsiteId($store->getBaseStore()->getWebsiteId());

        $product->unsetData('final_price');
        $product->unsetData('calculated_final_price');
        $basePrice = $this->applyTaxRateOnPrice($product->getPrice(), $taxRate);
        $specialPrice = $this->applyTaxRateOnPrice($product->getSpecialPrice(), $taxRate);
        $finalPrice = $this->applyTaxRateOnPrice($product->getFinalPrice(1), $taxRate);

        $product->setCustomerGroupId($originalCustomerGroupId);
        $product->setWebsiteId($originalWebsiteId);

        return [
            self::KEY_BASE_PRICE => round($basePrice, 2),
            self::KEY_SPECIAL_PRICE => ($specialPrice > 0) ? round($specialPrice, 2) : '',
            self::KEY_FINAL_PRICE => round($finalPrice, 2),
            self::KEY_SPECIAL_PRICE_FROM_DATE => $this->getDateValue($store, $product, 'special_from_date'),
            self::KEY_SPECIAL_PRICE_TO_DATE => $this->getDateValue($store, $product, 'special_to_date'),
        ];
    }

    /**
     * @param float $priceA
     * @param float $priceB
     * @param string $priceType
     * @return int
     */
    private function compareVariationPricesPriority($priceA, $priceB, $priceType)
    {
        return (ConfigInterface::CONFIGURABLE_PRODUCT_PRICE_TYPE_VARIATIONS_MINIMUM === $priceType)
            ? $priceB <=> $priceA
            : $priceA <=> $priceB;
    }

    /**
     * @param CatalogProduct $product
     * @param StoreInterface $store
     * @param string $priceType
     * @return array
     */
    private function getConfigurablePriceData(CatalogProduct $product, StoreInterface $store, $priceType)
    {
        $variationCollection = $this->configurableProductType->getUsedProductCollection($product);
        $variationCollection->addStoreFilter($store->getBaseStoreId());
        $this->prepareLoadableProductCollection($store, $variationCollection);
        $priceData = [];
        $currentFinalPrice = 0;

        /** @var CatalogProduct $variation */
        foreach ($variationCollection as $variation) {
            $variationPriceData = $this->getSimpleProductPriceData($variation, $store);
            $variationFinalPrice = $variationPriceData[self::KEY_FINAL_PRICE] ?? 0;

            if (!empty($variationFinalPrice)) {
                if (empty($priceData)
                    || (0 < $this->compareVariationPricesPriority($variationFinalPrice, $currentFinalPrice, $priceType))
                ) {
                    $priceData = $variationPriceData;
                    $currentFinalPrice = $variationFinalPrice;
                }
            }
        }

        return $priceData;
    }

    public function getProductData(StoreInterface $store, RefreshableProduct $product)
    {
        $catalogProduct = $product->getCatalogProduct();
        $productTypeId = $catalogProduct->getTypeId();
        $productData = [];

        if (CatalogProductType::TYPE_SIMPLE === $productTypeId) {
            $productData = $this->getSimpleProductPriceData($catalogProduct, $store);
        } elseif (ConfigurableProductType::TYPE_CODE === $productTypeId) {
            $priceType = $this->getConfig()->getConfigurableProductPriceType($store);

            if (ConfigInterface::CONFIGURABLE_PRODUCT_PRICE_TYPE_NONE !== $priceType) {
                $productData = $this->getConfigurablePriceData($catalogProduct, $store, $priceType);
            }
        }

        return $productData;
    }

    public function exportBaseProductData(
        StoreInterface $store,
        array $productData,
        AbstractExportedProduct $exportedProduct
    ) {
        if (isset($productData[self::KEY_FINAL_PRICE])) {
            $exportedProduct->setPrice($productData[self::KEY_FINAL_PRICE]);
        }

        if (isset($productData[self::KEY_BASE_PRICE])) {
            $exportedProduct->setAttribute('price_before_discount', $productData[self::KEY_BASE_PRICE]);
        }
    }
}
