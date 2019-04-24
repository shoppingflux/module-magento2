<?php

namespace ShoppingFeed\Manager\Plugin\Weee;

use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Store\Model\Website;
use Magento\Weee\Model\Tax as WeeeTax;

class TaxPlugin
{
    /**
     * @var array
     */
    private $lockedProductAttributes = array();

    /**
     * @param WeeeTax $subject
     * @param callable $proceed
     * @param CatalogProduct $product
     * @param DataObject $shipping
     * @param DataObject $billing
     * @param Website $website
     * @param bool $calculateTax
     * @param bool $round
     * @return array
     */
    public function aroundGetProductWeeeAttributes(
        WeeeTax $subject,
        callable $proceed,
        $product,
        $shipping,
        $billing,
        $website,
        $calculateTax,
        $round
    ) {
        $productAttributes = $proceed($product, $shipping, $billing, $website, $calculateTax, $round);

        if (($product instanceof DataObject)
            && ($productId = (int) $product->getId())
            && isset($this->lockedProductAttributes[$productId])
            && ($shipping instanceof QuoteAddress)
            && ($extensionAttributes = $shipping->getExtensionAttributes())
            && $extensionAttributes->getSfmIsShoppingFeedOrder()
        ) {
            return $this->lockedProductAttributes[$productId];
        }

        return $productAttributes;
    }

    /**
     * @param int $productId
     * @param array $productAttributes
     */
    public function setProductLockedAttributes($productId, array $productAttributes)
    {
        $this->lockedProductAttributes[$productId] = $productAttributes;
    }

    /**
     * @param int $productId
     */
    public function resetProductLockedAttributes($productId)
    {
        if (isset($this->lockedProductAttributes[$productId])) {
            unset($this->lockedProductAttributes[$productId]);
        }
    }
}
