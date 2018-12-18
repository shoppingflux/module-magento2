<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Config;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\ConfigInterface;

interface AttributesInterface extends ConfigInterface
{
    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldUseProductIdForSku(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return AbstractAttribute|null
     */
    public function getBrandAttribute(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return AbstractAttribute|null
     */
    public function getDescriptionAttribute(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return AbstractAttribute|null
     */
    public function getShortDescriptionAttribute(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return AbstractAttribute|null
     */
    public function getGtinAttribute(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return AbstractAttribute[]
     */
    public function getAttributeMap(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return AbstractAttribute[]
     */
    public function getAllAttributes(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldExportAttributeSetName(StoreInterface $store);
}
