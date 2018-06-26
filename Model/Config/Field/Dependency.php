<?php

namespace ShoppingFeed\Manager\Model\Config\Field;


class Dependency
{
    /**
     * @var array
     */
    private $values;

    /**
     * @var string[]
     */
    private $fieldNames;

    /**
     * @param array $values
     * @param string[] $fieldNames
     */
    public function __construct(array $values, array $fieldNames)
    {
        $this->values = $values;
        $this->fieldNames = $fieldNames;
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @return string[]
     */
    public function getFieldNames()
    {
        return $this->fieldNames;
    }
}
