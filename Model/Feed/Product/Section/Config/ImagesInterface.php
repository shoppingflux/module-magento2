<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Config;

use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\ConfigInterface;

interface ImagesInterface extends ConfigInterface
{
    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldExportAllImages(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return int
     */
    public function getExportedImageCount(StoreInterface $store);
}
