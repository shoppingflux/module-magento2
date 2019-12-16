<?php

namespace ShoppingFeed\Manager\Model;

class LabelledValue
{
    /**
     * @var string
     */
    private $value;

    /**
     * @var string
     */
    private $label;

    /**
     * @param string $value
     * @param string $label
     */
    public function __construct($value, $label)
    {
        $this->value = (string) $value;
        $this->label = (string) $label;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = (string) $value;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = (string) $label;
    }
}
