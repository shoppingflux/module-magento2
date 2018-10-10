<?php

namespace ShoppingFeed\Manager\Model\Config\Basic;

use ShoppingFeed\Manager\Model\Config\FieldInterface;

interface ConfigInterface
{
    /**
     * @return FieldInterface[]
     */
    public function getFields();

    /**
     * @param string $name
     * @return FieldInterface|null
     */
    public function getField($name);

    /**
     * @param array $data
     * @return array
     */
    public function prepareRawDataForForm(array $data);

    /**
     * @param array $data
     * @return array
     */
    public function prepareFormDataForSave(array $data);
}
