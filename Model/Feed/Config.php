<?php

namespace ShoppingFeed\Manager\Model\Feed;

use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\DataObject;
use ShoppingFeed\Manager\Model\Config\Field\Checkbox;

class Config extends AbstractConfig implements ConfigInterface
{
    const KEY_USE_GZIP_COMPRESSION = 'use_gzip_compression';
    const KEY_FORCE_SECURE_URLS = 'force_secure_urls';

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

            $this->fieldFactory->create(
                Checkbox::TYPE_CODE,
                [
                    'name' => self::KEY_FORCE_SECURE_URLS,
                    'isCheckedByDefault' => false,
                    'label' => __('Force Use of Secure URLs'),
                    'checkedNotice' => $this->getTranslatedMultiLineString(
                        [
                            'The product, category and image URLs, as well as the feed URL, will always use the secure base URLs of the store view.',
                            'This option only has an effect if the secure base URLs configured for the store view differ from the unsecure ones.',
                        ]
                    ),
                    'uncheckedNotice' => $this->getTranslatedMultiLineString(
                        [
                            'Depending on the context (e.g. cron jobs), the exported URLs may use the unsecure base URLs of the store view.',
                            'This option only has an effect if the secure base URLs configured for the store view differ from the unsecure ones.',
                        ]
                    ),
                    'sortOrder' => 20,
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
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldForceSecureUrls(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_FORCE_SECURE_URLS);
    }

    public function isSectionRefreshNeededForStoreDataChange(
        StoreInterface $store,
        DataObject $dataA,
        DataObject $dataB
    ) {
        $irrelevantFieldNames = [ self::KEY_USE_GZIP_COMPRESSION ];

        return !$this->isEqualStoreDataIgnoringFields($store, $dataA, $dataB, $irrelevantFieldNames);
    }

    /**
     * @return string
     */
    public function getFieldsetLabel()
    {
        return __('Feed - General');
    }
}
