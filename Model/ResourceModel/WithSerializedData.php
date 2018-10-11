<?php

namespace ShoppingFeed\Manager\Model\ResourceModel;

use Magento\Framework\DataObject;
use Magento\Framework\Model\AbstractModel;

trait WithSerializedData
{
    /**
     * @param AbstractModel $object
     * @param string[] $dataObjectFieldNames
     * @param callable $prepareCallback
     * @return $this
     */
    protected function prepareDataForSaveWithSerialized(
        AbstractModel $object,
        array $dataObjectFieldNames,
        callable $prepareCallback
    ) {
        $dataObjects = [];

        foreach ($dataObjectFieldNames as $key) {
            $dataObject = $object->getDataUsingMethod($key);

            if ($dataObject instanceof DataObject) {
                $dataObjects[$key] = $dataObject;
                $object->unsetData($key);
            }
        }

        $preparedData = $prepareCallback($object);

        foreach ($dataObjectFieldNames as $key) {
            if (isset($dataObjects[$key])) {
                $preparedData[$key] = json_encode((object) $dataObjects[$key]->getData());
                $object->setData($key, $dataObjects[$key]);
            }
        }

        return $preparedData;
    }

    /**
     * @param AbstractModel $object
     * @param array $dataObjectFieldNames
     * @param callable $prepareCallback
     * @return $this
     */
    protected function prepareDataForUpdateWithSerialized(
        $object,
        array $dataObjectFieldNames,
        callable $prepareCallback
    ) {
        $baseData = [];

        foreach ($dataObjectFieldNames as $key) {
            $baseData[$key] = $object->getData($key);
            $jsonData = '';

            if ($baseData[$key] instanceof DataObject) {
                $jsonData = json_encode((object) $baseData[$key]->getData());
            } elseif (is_array($baseData[$key])) {
                $jsonData = json_encode((object) $baseData[$key]);
            } elseif (is_string($baseData[$key])) {
                $jsonData = $baseData[$key];
            }

            $object->setData($key, $jsonData);
        }

        $preparedData = $prepareCallback($object);

        foreach ($dataObjectFieldNames as $key) {
            $object->setData($key, $baseData[$key]);
        }

        return $preparedData;
    }
}
