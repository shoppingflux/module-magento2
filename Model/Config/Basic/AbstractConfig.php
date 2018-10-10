<?php

namespace ShoppingFeed\Manager\Model\Config\Basic;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
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
    private $fields;

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
    abstract protected function getBaseFields();

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
     * @return FieldInterface[]
     * @throws LocalizedException
     */
    final public function getFields()
    {
        if (!is_array($this->fields)) {
            $this->fields = [];
            $baseFields = $this->getBaseFields();

            foreach ($baseFields as $field) {
                $this->checkFieldValidity($field);
                $this->fields[$field->getName()] = $field;
            }
        }

        return $this->fields;
    }

    final public function getField($name)
    {
        $fields = $this->getFields();
        return isset($fields[$name]) ? $fields[$name] : null;
    }

    /**
     * @param string $name
     * @param DataObject $configData
     * @return mixed|null
     */
    protected function getFieldValue($name, DataObject $configData)
    {
        $field = $this->getField($name);

        if (null === $field) {
            return null;
        }

        return !$configData->hasData($name)
            ? $field->getDefaultUseValue()
            : $field->prepareRawValueForUse($configData->getDataByKey($name));
    }

    /**
     * @param array $data
     * @return array
     * @throws LocalizedException
     */
    public function prepareFormDataForSave(array $data)
    {
        $preparedData = [];

        foreach ($this->getFields() as $field) {
            $fieldName = $field->getName();

            if (isset($data[$fieldName])) {
                $preparedData[$fieldName] = $field->prepareFormValueForSave($data[$fieldName]);
            }
        }

        return $preparedData;
    }

    /**
     * @param array $data
     * @return array
     * @throws LocalizedException
     */
    public function prepareRawDataForForm(array $data)
    {
        $preparedData = [];

        foreach ($this->getFields() as $field) {
            $fieldName = $field->getName();

            if (array_key_exists($fieldName, $data)) {
                $preparedData[$fieldName] = $field->prepareRawValueForForm($data[$fieldName]);
            }
        }

        return $preparedData;
    }
}
