<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Export\State;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Account\Store\ConfigInterface as BaseConfig;
use ShoppingFeed\Manager\Model\Feed\Product\RefreshableConfigInterface;

interface ConfigInterface extends BaseConfig, RefreshableConfigInterface
{
    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldExportSelectedOnly(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return AbstractAttribute|null
     */
    public function getIsSelectedProductAttribute(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return int[]
     */
    public function getExportedVisibilities(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldExportOutOfStock(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldExportNotSalable(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return int
     */
    public function getChildrenExportMode(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldRetainPreviouslyExported(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return int
     */
    public function getPreviouslyExportedRetentionDuration(StoreInterface $store);
}
