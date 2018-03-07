<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Config;

use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Account\Store\Config\Field\Checkbox;
use ShoppingFeed\Manager\Model\Account\Store\Config\Field\TextBox;
use ShoppingFeed\Manager\Model\Account\Store\Config\Value\Handler\PositiveInteger as PositiveIntegerHandler;
use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractConfig;


class Images extends AbstractConfig implements ImagesInterface
{
    const KEY_EXPORT_ALL_IMAGES = 'export_all_images';
    const KEY_EXPORTED_IMAGE_COUNT = 'exported_image_count';

    protected function getBaseFields()
    {
        return array_merge(
            [
                new Checkbox(
                    self::KEY_EXPORT_ALL_IMAGES,
                    __('Export All Images'),
                    true,
                    '',
                    '',
                    [],
                    [ self::KEY_EXPORTED_IMAGE_COUNT ]
                ),

                new TextBox(
                    self::KEY_EXPORTED_IMAGE_COUNT,
                    new PositiveIntegerHandler(),
                    __('Exported Image Count'),
                    true,
                    5,
                    5,
                    ''
                ),
            ],
            parent::getBaseFields()
        );
    }

    public function getFieldsetLabel()
    {
        return __('Feed - Images Section');
    }

    public function shouldExportAllImages(StoreInterface $store)
    {
        return $this->getStoreFieldValue($store, self::KEY_EXPORT_ALL_IMAGES);
    }

    public function getExportedImageCount(StoreInterface $store)
    {
        return $this->getStoreFieldValue($store, self::KEY_EXPORTED_IMAGE_COUNT);
    }
}
