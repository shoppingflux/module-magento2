<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Config;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\ConfigInterface;


interface ShippingInterface extends ConfigInterface
{
    /**
     * @param StoreInterface $store
     * @return AbstractAttribute|null
     */
    public function getCarrierNameAttribute(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return AbstractAttribute|null
     */
    public function getFeesAttribute(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return AbstractAttribute|null
     */
    public function getDelayAttribute(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return string|null
     */
    public function getDefaultCarrierName(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return float|null
     */
    public function getDefaultFees(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return string|null
     */
    public function getDefaultDelay(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return AbstractAttribute[]
     */
    public function getAllAttributes(StoreInterface $store);
}
