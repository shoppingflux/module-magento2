<?php

namespace ShoppingFeed\Manager\Model\Account\Store;

use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\DataObject;
use ShoppingFeed\Manager\Model\Config\FieldFactoryInterface;
use ShoppingFeed\Manager\Model\Config\FieldInterface;
use ShoppingFeed\Manager\Model\Config\Value\HandlerFactoryInterface as ValueHandlerFactoryInterface;

abstract class AbstractConfig implements ConfigInterface
{
    /**
     * @var FieldFactoryInterface
     */
    protected $fieldFactory;

    /**
     * @var ValueHandlerFactoryInterface
     */
    protected $valueHandlerFactory;

    /**
     * @var FieldInterface[]
     */
    private $baseFields;

    /**
     * @var FieldInterface[][]
     */
    private $storeFields = [];

    /**
     * @var array[]
     */
    private $valueCache = [];

    /**
     * @param FieldFactoryInterface $fieldFactory
     * @param ValueHandlerFactoryInterface $valueHandlerFactory
     */
    public function __construct(FieldFactoryInterface $fieldFactory, ValueHandlerFactoryInterface $valueHandlerFactory)
    {
        $this->fieldFactory = $fieldFactory;
        $this->valueHandlerFactory = $valueHandlerFactory;
    }

    /**
     * @return FieldInterface[]
     */
    protected function getBaseFields()
    {
        return [];
    }

    /**
     * @param StoreInterface $store
     * @return FieldInterface[]
     */
    protected function getStoreFields(StoreInterface $store)
    {
        return [];
    }

    /**
     * @param mixed $field
     * @throws LocalizedException
     */
    private function checkFieldValidity($field)
    {
        if (!$field instanceof FieldInterface) {
            throw new LocalizedException(
                __(
                    'Config fields must be of type: ShoppingFeed\Manager\Model\Config\FieldInterface.'
                )
            );
        }
    }

    /**
     * @param StoreInterface $store
     * @return FieldInterface[]
     * @throws LocalizedException
     */
    final public function getFields(StoreInterface $store)
    {
        if (!is_array($this->baseFields)) {
            $this->baseFields = [];
            $baseFields = $this->getBaseFields();

            foreach ($baseFields as $field) {
                $this->checkFieldValidity($field);
                $this->baseFields[$field->getName()] = $field;
            }
        }

        $storeId = $store->getId();

        if (!isset($this->storeFields[$storeId])) {
            $this->storeFields[$storeId] = [];
            $storeFields = $this->getStoreFields($store);

            foreach ($storeFields as $field) {
                $this->checkFieldValidity($field);
                $this->storeFields[$storeId][$field->getName()] = $field;
            }
        }

        return array_merge($this->baseFields, $this->storeFields[$storeId]);
    }

    final public function getField(StoreInterface $store, $name)
    {
        $fields = $this->getFields($store);
        return isset($fields[$name]) ? $fields[$name] : null;
    }

    /**
     * @param FieldInterface $field
     * @param string $valuePath
     * @param DataObject $data
     * @return mixed
     */
    private function getDataFieldValue(FieldInterface $field, $valuePath, DataObject $data)
    {
        return !$data->hasDataForPath($valuePath)
            ? $field->getDefaultUseValue()
            : $field->prepareRawValueForUse($data->getDataByPath($valuePath));
    }

    /**
     * @param string $fieldName
     * @return string
     */
    protected function getFieldValuePath($fieldName)
    {
        return $this->getScope() . '/' . implode('/', $this->getScopeSubPath()) . '/' . $fieldName;
    }

    /**
     * @param StoreInterface $store
     * @param string $fieldName
     * @return mixed|null
     */
    protected function getFieldValue(StoreInterface $store, $fieldName)
    {
        $storeId = $store->getId();
        $field = $this->getField($store, $fieldName);

        if (null === $field) {
            return null;
        }

        if (isset($this->valueCache[$storeId]) && array_key_exists($fieldName, $this->valueCache[$storeId])) {
            return $this->valueCache[$storeId][$fieldName];
        }

        $this->valueCache[$storeId][$fieldName] = $this->getDataFieldValue(
            $field,
            $this->getFieldValuePath($fieldName),
            $store->getConfiguration()
        );

        return $this->valueCache[$storeId][$fieldName];
    }

    /**
     * @param StoreInterface $store
     * @param DataObject $dataA
     * @param DataObject $dataB
     * @return bool
     * @throws LocalizedException
     */
    public function isEqualStoreData(StoreInterface $store, DataObject $dataA, DataObject $dataB)
    {
        $isEqualStoreData = true;
        $baseValuePath = $this->getScope() . '/' . implode('/', $this->getScopeSubPath()) . '/';

        foreach ($this->getFields($store) as $fieldName => $field) {
            $valueA = $this->getDataFieldValue($field, $baseValuePath . $fieldName, $dataA);
            $valueB = $this->getDataFieldValue($field, $baseValuePath . $fieldName, $dataB);

            if (!$field->isEqualValues($valueA, $valueB)) {
                $isEqualStoreData = false;
                break;
            }
        }

        return $isEqualStoreData;
    }
}
