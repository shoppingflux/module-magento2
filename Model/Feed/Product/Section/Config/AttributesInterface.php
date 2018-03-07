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
     * @return AbstractAttribute[]
     */
    public function getAttributeMap(StoreInterface $store);
}
