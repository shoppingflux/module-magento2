<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Config;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Account\Store\Config\Field\MultiSelect;
use ShoppingFeed\Manager\Model\Account\Store\Config\Field\Select;
use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractConfig;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Config\Attribute\SourceInterface as AttributeSourceInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Config\Value\Handler\Attribute as AttributeHandler;


class Attributes extends AbstractConfig implements AttributesInterface
{
    const KEY_USE_PRODUCT_ID_FOR_SKU = 'use_product_id_for_sku';
    const KEY_BASE_MAPPED_ATTRIBUTE = 'attribute_for_%s';
    const KEY_ADDITIONAL_ATTRIBUTES = 'additional_attributes';

    /**
     * @var AttributeSourceInterface
     */
    private $attributeSource;

    /**
     * @var string[]
     */
    protected $mappableAttributes;

    /**
     * @param AttributeSourceInterface $attributeSource
     * @param string[] $mappableAttributes
     */
    public function __construct(AttributeSourceInterface $attributeSource, array $mappableAttributes = [])
    {
        $this->attributeSource = $attributeSource;
        $this->mappableAttributes = array_filter($mappableAttributes);
    }

    protected function getBaseFields()
    {
        $fields = [];
        $attributeValueHandler = new AttributeHandler($this->attributeSource);

        foreach ($this->mappableAttributes as $attributeCode => $attributeLabel) {
            $fieldName = sprintf(self::KEY_BASE_MAPPED_ATTRIBUTE, $attributeCode);

            $fields[] = new Select(
                $fieldName,
                $attributeValueHandler,
                $attributeLabel,
                false
            );
        }

        $fields[] = new MultiSelect(
            self::KEY_ADDITIONAL_ATTRIBUTES,
            $attributeValueHandler,
            __('Additional Attributes')
        );

        return array_merge($fields, parent::getBaseFields());
    }

    public function getFieldsetLabel()
    {
        return __('Feed - Attributes Section');
    }

    public function shouldUseProductIdForSku(StoreInterface $store)
    {
        return $this->getStoreFieldValue($store, self::KEY_USE_PRODUCT_ID_FOR_SKU);
    }

    public function getAttributeMap(StoreInterface $store)
    {
        $map = [];

        foreach ($this->mappableAttributes as $attributeCode => $attributeLabel) {
            $fieldName = sprintf(self::KEY_BASE_MAPPED_ATTRIBUTE, $attributeCode);
            $map[$attributeCode] = $this->getStoreFieldValue($store, $fieldName);
        }

        $additionalAttributes = $this->getStoreFieldValue($store, self::KEY_ADDITIONAL_ATTRIBUTES);

        /** @var AbstractAttribute $attribute */
        foreach ($additionalAttributes as $attribute) {
            $attributeCode = $attribute->getAttributeCode();

            if (!isset($map[$attributeCode])) {
                $map[$attributeCode] = $attribute;
            }
        }

        return array_filter($map);
    }
}
