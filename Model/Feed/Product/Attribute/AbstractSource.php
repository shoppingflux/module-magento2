<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Attribute;

abstract class AbstractSource implements SourceInterface
{
    /**
     * @var array|null
     */
    private $attributeOptionArray = null;

    public function getAttributeOptionArray($withEmpty = true)
    {
        if (!is_array($this->attributeOptionArray)) {
            $this->attributeOptionArray = [];

            foreach ($this->getAttributesByCode() as $attributeCode => $productAttribute) {
                $this->attributeOptionArray[] = [
                    'value' => $attributeCode,
                    'label' => __('%1 (%2)', $attributeCode, $productAttribute->getFrontend()->getLabel()),
                ];
            }
        }

        $result = $this->attributeOptionArray;

        if ($withEmpty) {
            array_unshift($result, [ 'value' => '', 'label' => __('None') ]);
        }

        return $result;
    }

    public function getAttribute($code)
    {
        $attributes = $this->getAttributesByCode();
        return isset($attributes[$code]) ? $attributes[$code] : null;
    }
}
