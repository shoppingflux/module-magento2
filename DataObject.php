<?php

namespace ShoppingFeed\Manager;

use Magento\Framework\DataObject as BaseDataObject;
use ShoppingFeed\Manager\Model\TimeHelper;

class DataObject extends BaseDataObject
{
    /**
     * @var TimeHelper
     */
    private $timeHelper;

    /**
     * @var string[]
     */
    protected $timestampFields = [];

    /**
     * @param TimeHelper $timeHelper
     * @param array $data
     */
    public function __construct(TimeHelper $timeHelper, array $data = [])
    {
        $this->timeHelper = $timeHelper;
        parent::__construct($data);
    }

    /**
     * @param string $dateTimeValue
     * @return int|null
     */
    private function getDateTimeTimestamp($dateTimeValue)
    {
        return preg_match('/^\d{4}-\d{2}\-\d{2}(?: \d{2}:\d{2}:\d{2})$/', $dateTimeValue)
            ? strtotime($dateTimeValue)
            : null;
    }

    public function setData($key, $value = null)
    {
        parent::setData($key, $value);

        if (is_array($key)) {
            $updatedDateTimes = array_intersect_key($this->timestampFields, $key);

            foreach ($updatedDateTimes as $baseKey => $timestampKey) {
                $this->setData($timestampKey, $key[$baseKey]);
            }
        } elseif (isset($this->timestampFields[$key])) {
            $this->setData($this->timestampFields[$key], $this->getDateTimeTimestamp($value));
        }

        return $this;
    }

    /**
     * @param string $path
     * @param mixed $value
     * @return $this
     */
    public function setDataByPath($path, $value = null)
    {
        $data = &$this->_data;
        $keys = explode('/', $path);

        foreach ($keys as $key) {
            if (!isset($data[$key])
                || !(is_array($data[$key]) || ($data[$key] instanceof BaseDataObject))
            ) {
                $data[$key] = [];
            }

            if ($data[$key] instanceof BaseDataObject) {
                $data = &$data[$key]->_data;
            } else {
                $data = &$data[$key];
            }
        }

        $data = $value;
        return $this;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function unsetDataByPath($path)
    {
        $data = &$this->_data;
        $pathKeys = explode('/', $path);
        $valueKey = array_pop($pathKeys);
        $isExistingPath = true;

        foreach ($pathKeys as $key) {
            if (isset($data[$key])) {
                if (is_array($data[$key])) {
                    $data = &$data[$key];
                    continue;
                } elseif ($data[$key] instanceof BaseDataObject) {
                    $data = &$data[$key]->_data;
                    continue;
                }
            }

            $isExistingPath = false;
            break;
        }

        if ($isExistingPath && isset($data[$valueKey])) {
            unset($data[$valueKey]);
        }

        return $this;
    }

    /**
     * @param string $path
     * @return bool
     */
    public function hasDataForPath($path)
    {
        $data = &$this->_data;
        $pathKeys = explode('/', $path);
        $valueKey = array_pop($pathKeys);
        $hasData = true;

        foreach ($pathKeys as $key) {
            if (isset($data[$key]) && (is_array($data[$key]) || ($data[$key] instanceof BaseDataObject))) {
                if ($data[$key] instanceof BaseDataObject) {
                    $data = &$data[$key]->_data;
                } else {
                    $data = &$data[$key];
                }
            } else {
                $hasData = false;
                break;
            }
        }

        if ($hasData) {
            $hasData = array_key_exists($valueKey, $data);
        }

        return $hasData;
    }
}
