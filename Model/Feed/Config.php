<?php

namespace ShoppingFeed\Manager\Model\Feed;

use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Config\Field\Checkbox;


class Config extends AbstractConfig implements ConfigInterface
{
    const KEY_USE_GZIP_COMPRESSION = 'use_gzip_compression';

    public function getScopeSubPath()
    {
        return [ 'general' ];
    }

    protected function getBaseFields()
    {
        return [
            $this->fieldFactory->create(
                Checkbox::TYPE_CODE,
                [
                    'name' => self::KEY_USE_GZIP_COMPRESSION,
                    'label' => __('Use Gzip Compression'),
                    'sortOrder' => 10,
                ]
            ),
        ];
    }

    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldUseGzipCompression(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_USE_GZIP_COMPRESSION);
    }

    /**
     * @return string
     */
    public function getFieldsetLabel()
    {
        return __('Feed - General');
    }
}
