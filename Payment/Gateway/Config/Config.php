<?php

namespace ShoppingFeed\Manager\Payment\Gateway\Config;

use Magento\Payment\Gateway\Config\Config as BaseConfig;

class Config extends BaseConfig
{
    const FIELD_NAME_TITLE = 'title';

    /**
     * @var array
     */
    static private $forcedStoreValues = [];

    /**
     * @var array
     */
    static private $forcedGlobalValues = [];

    /**
     * @param string $field
     * @param string $value
     * @param int|null $storeId
     */
    public function setForcedValue($field, $value, $storeId = null)
    {
        if (null === $storeId) {
            self::$forcedGlobalValues[$field] = $value;
        } else {
            self::$forcedStoreValues[(int) $storeId][$field] = $value;
        }
    }

    /**
     * @param string $field
     * @param int|null $storeId
     */
    public function unsetForcedValue($field, $storeId = null)
    {
        if (null === $storeId) {
            if (isset(self::$forcedGlobalValues[$field])) {
                unset(self::$forcedGlobalValues[$field]);
            }
        } else {
            $storeId = (int) $storeId;

            if (
                isset(self::$forcedStoreValues[$storeId])
                && isset(self::$forcedStoreValues[$storeId][$field])
            ) {
                unset(self::$forcedStoreValues[$storeId][$field]);
            }
        }
    }

    public function getValue($field, $storeId = null)
    {
        if (null !== $storeId) {
            if (
                isset(self::$forcedStoreValues[$storeId])
                && isset(self::$forcedStoreValues[$storeId][$field])
            ) {
                return self::$forcedStoreValues[$storeId][$field];
            }
        }

        if (isset(self::$forcedGlobalValues[$field])) {
            return self::$forcedGlobalValues[$field];
        }

        return parent::getValue($field, $storeId);
    }
}
