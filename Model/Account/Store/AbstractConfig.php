<?php

namespace ShoppingFeed\Manager\Model\Account\Store;

use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Account\Store\Config\AbstractField;


abstract class AbstractConfig implements ConfigInterface
{
    /**
     * @var AbstractField[]
     */
    private $fields;

    /**
     * @var array
     */
    private $cache = [];

    /**
     * @return AbstractField[]
     */
    abstract protected function getBaseFields();

    /**
     * @return AbstractField[]
     * @throws LocalizedException
     */
    public function getFields()
    {
        if (!is_array($this->fields)) {
            $this->fields = [];
            $baseFields = $this->getBaseFields();

            foreach ($baseFields as $field) {
                if (!$field instanceof AbstractField) {
                    throw new LocalizedException(
                        __('Config field must be of type ShoppingFeed\Manager\Model\Account\Store\Config\AbstractField')
                    );
                }

                $this->fields[$field->getName()] = $field;
            }
        }

        return $this->fields;
    }

    public function getField($name)
    {
        $fields = $this->getFields();
        return isset($fields[$name]) ? $fields[$name] : null;
    }

    public function getStoreFieldValue(StoreInterface $store, $name)
    {
        $field = $this->getField($name);

        if (null === $field) {
            return null;
        }

        if (array_key_exists($name, $this->cache)) {
            return $this->cache[$name];
        }

        $configuration = $store->getConfiguration();
        $path = $this->getScope() . '/' . implode('/', $this->getScopeSubPath()) . '/' . $name;

        $this->cache[$name] = !$configuration->hasDataForPath($path)
            ? $field->getDefaultUseValue()
            : $field->prepareRawValueForUse($configuration->getDataByPath($path));

        return $this->cache[$name];
    }
}
