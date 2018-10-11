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
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setDataByPath($key, $value = null)
    {
        $data = &$this->_data;
        $keys = explode('/', $key);

        foreach ($keys as $key) {
            if (!isset($data[$key])
                || !(is_array($data[$key]) || ($data[$key] instanceof DataObject))
            ) {
                $data[$key] = [];
            }

            if ($data[$key] instanceof DataObject) {
                $data = &$data[$key]->_data;
            } else {
                $data = &$data[$key];
            }
        }

        $data = $value;
        return $this;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function hasDataForPath($key)
    {
        $data = &$this->_data;
        $keys = explode('/', $key);
        $lastKey = array_pop($keys);
        $hasData = true;

        foreach ($keys as $key) {
            if (isset($data[$key]) && (is_array($data[$key]) || ($data[$key] instanceof DataObject))) {
                if ($data[$key] instanceof DataObject) {
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
            $hasData = array_key_exists($lastKey, $data);
        }

        return $hasData;
    }
}
