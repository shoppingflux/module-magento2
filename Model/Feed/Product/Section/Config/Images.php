<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Config;

use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Config\Field\Checkbox;
use ShoppingFeed\Manager\Model\Config\Field\TextBox;
use ShoppingFeed\Manager\Model\Config\Value\Handler\PositiveInteger as PositiveIntegerHandler;
use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractConfig;


class Images extends AbstractConfig implements ImagesInterface
{
    const KEY_EXPORT_ALL_IMAGES = 'export_all_images';
    const KEY_EXPORTED_IMAGE_COUNT = 'exported_image_count';

    protected function getBaseFields()
    {
        return array_merge(
            [
                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_EXPORT_ALL_IMAGES,
                        'isCheckedByDefault' => true,
                        'label' => __('Export All Images'),
                        'uncheckedDependentFieldNames' => [ self::KEY_EXPORTED_IMAGE_COUNT ],
                        'sortOrder' => 10,
                    ]
                ),

                $this->fieldFactory->create(
                    TextBox::TYPE_CODE,
                    [
                        'name' => self::KEY_EXPORTED_IMAGE_COUNT,
                        'valueHandler' => $this->valueHandlerFactory->create(PositiveIntegerHandler::TYPE_CODE),
                        'isRequired' => true,
                        'defaultFormValue' => 5,
                        'defaultUseValue' => 5,
                        'label' => __('Exported Image Count'),
                        'sortOrder' => 20,
                    ]
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
        return $this->getFieldValue($store, self::KEY_EXPORT_ALL_IMAGES);
    }

    public function getExportedImageCount(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_EXPORTED_IMAGE_COUNT);
    }
}
