<?php

namespace ShoppingFeed\Manager\Model\Source;

trait WithOptionHash
{
    /**
     * @return array
     */
    abstract public function toOptionArray();

    /**
     * @return array
     */
    public function toOptionHash()
    {
        $optionHash = [];
        $optionArray = $this->toOptionArray();

        foreach ($optionArray as $option) {
            if (is_array($option) && isset($option['value']) && isset($option['label'])) {
                $optionHash[$option['value']] = $option['label'];
            }
        }

        return $optionHash;
    }
}
