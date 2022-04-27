<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Config;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\ConfigInterface;

interface PricesInterface extends ConfigInterface
{
    const DISCOUNT_EXPORT_MODE_DISCOUNT_ATTRIBUTE = 'discount_attribute';
    const DISCOUNT_EXPORT_MODE_PRICE_ATTRIBUTE = 'price_attribute';

    const CONFIGURABLE_PRODUCT_PRICE_TYPE_NONE = 'none';
    const CONFIGURABLE_PRODUCT_PRICE_TYPE_VARIATIONS_MINIMUM = 'variations_minimum';
    const CONFIGURABLE_PRODUCT_PRICE_TYPE_VARIATIONS_MAXIMUM = 'variations_maximum';

    /**
     * @param StoreInterface $store
     * @return int
     */
    public function getCustomerGroupId(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return string
     */
    public function getDiscountExportMode(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return string
     */
    public function getConfigurableProductPriceType(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return AbstractAttribute|null
     */
    public function getEcotaxAttribute(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return string|null
     */
    public function getEcotaxCountry(StoreInterface $store);
}
