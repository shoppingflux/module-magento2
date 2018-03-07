<?php

namespace ShoppingFeed\Manager;

use Magento\Framework\DataObject as BaseDataObject;


class DataObject extends BaseDataObject
{
    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setDataByPath($key, $value = null)
    {
        $data =& $this->_data;
        $keys = explode('/', $key);

        foreach ($keys as $key) {
            if (!isset($data[$key])
                || !(is_array($data[$key]) || ($data[$key] instanceof DataObject))
            ) {
                $data[$key] = [];
            }

            if ($data[$key] instanceof DataObject) {
                $data =& $data[$key]->_data;
            } else {
                $data =& $data[$key];
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
        $data =& $this->_data;
        $keys = explode('/', $key);
        $lastKey = array_pop($keys);
        $hasData = true;

        foreach ($keys as $key) {
            if (isset($data[$key]) && (is_array($data[$key]) || ($data[$key] instanceof DataObject))) {
                if ($data[$key] instanceof DataObject) {
                    $data =& $data[$key]->_data;
                } else {
                    $data =& $data[$key];
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
