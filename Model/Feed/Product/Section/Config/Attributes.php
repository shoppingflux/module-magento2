<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Config;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Config\Field\Checkbox;
use ShoppingFeed\Manager\Model\Config\Field\MultiSelect;
use ShoppingFeed\Manager\Model\Config\Field\Select;
use ShoppingFeed\Manager\Model\Config\FieldFactoryInterface;
use ShoppingFeed\Manager\Model\Config\Value\HandlerFactoryInterface as ValueHandlerFactoryInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractConfig;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\SourceInterface as AttributeSourceInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Config\Value\Handler\Attribute as AttributeHandler;

class Attributes extends AbstractConfig implements AttributesInterface
{
    const KEY_USE_PRODUCT_ID_FOR_SKU = 'use_product_id_for_sku';
    const KEY_BRAND_ATTRIBUTE = 'brand_attribute';
    const KEY_DESCRIPTION_ATTRIBUTE = 'description_attribute';
    const KEY_SHORT_DESCRIPTION_ATTRIBUTE = 'short_description_attribute';
    const KEY_GTIN_ATTRIBUTE = 'gtin_attribute';
    const KEY_BASE_MAPPED_ATTRIBUTE = 'attribute_for_%s';
    const KEY_ADDITIONAL_ATTRIBUTES = 'additional_attributes';
    const KEY_EXPORT_ATTRIBUTE_SET_NAME = 'export_attribute_set_name';

    /**
     * @var AttributeSourceInterface
     */
    private $attributeSource;

    /**
     * @var string[]
     */
    protected $mappableAttributes;

    /**
     * @param FieldFactoryInterface $fieldFactory
     * @param ValueHandlerFactoryInterface $valueHandlerFactory
     * @param AttributeSourceInterface $attributeSource
     * @param string[] $mappableAttributes
     */
    public function __construct(
        FieldFactoryInterface $fieldFactory,
        ValueHandlerFactoryInterface $valueHandlerFactory,
        AttributeSourceInterface $attributeSource,
        array $mappableAttributes = []
    ) {
        $this->attributeSource = $attributeSource;
        $this->mappableAttributes = array_filter($mappableAttributes);
        parent::__construct($fieldFactory, $valueHandlerFactory);
    }

    protected function getBaseFields()
    {
        $attributeValueHandler = $this->valueHandlerFactory->create(
            AttributeHandler::TYPE_CODE,
            [ 'attributeSource' => $this->attributeSource ]
        );

        $fields = [
            $this->fieldFactory->create(
                Checkbox::TYPE_CODE,
                [
                    'name' => self::KEY_USE_PRODUCT_ID_FOR_SKU,
                    'label' => __('Use Product ID for SKU'),
                    'sortOrder' => 10,
                ]
            ),

            $this->fieldFactory->create(
                Select::TYPE_CODE,
                [
                    'name' => self::KEY_BRAND_ATTRIBUTE,
                    'valueHandler' => $attributeValueHandler,
                    'isRequired' => false,
                    'label' => __('Brand Attribute'),
                    'sortOrder' => 20,
                ]
            ),

            $this->fieldFactory->create(
                Select::TYPE_CODE,
                [
                    'name' => self::KEY_DESCRIPTION_ATTRIBUTE,
                    'valueHandler' => $attributeValueHandler,
                    'isRequired' => false,
                    'label' => __('Description Attribute'),
                    'sortOrder' => 30,
                ]
            ),

            $this->fieldFactory->create(
                Select::TYPE_CODE,
                [
                    'name' => self::KEY_SHORT_DESCRIPTION_ATTRIBUTE,
                    'valueHandler' => $attributeValueHandler,
                    'isRequired' => false,
                    'label' => __('Short Description Attribute'),
                    'sortOrder' => 40,
                ]
            ),

            $this->fieldFactory->create(
                Select::TYPE_CODE,
                [
                    'name' => self::KEY_GTIN_ATTRIBUTE,
                    'valueHandler' => $attributeValueHandler,
                    'isRequired' => false,
                    'label' => __('GTIN Attribute'),
                    'sortOrder' => 50,
                ]
            ),
        ];

        $sortOrder = 60;

        foreach ($this->mappableAttributes as $attributeCode => $attributeLabel) {
            $fieldName = sprintf(self::KEY_BASE_MAPPED_ATTRIBUTE, $attributeCode);

            $fields[] = $this->fieldFactory->create(
                Select::TYPE_CODE,
                [
                    'name' => $fieldName,
                    'valueHandler' => $attributeValueHandler,
                    'isRequired' => false,
                    'label' => __('%1 Attribute', $attributeLabel),
                    'sortOrder' => $sortOrder,
                ]
            );

            $sortOrder += 10;
        }

        $fields[] = $this->fieldFactory->create(
            MultiSelect::TYPE_CODE,
            [
                'name' => self::KEY_ADDITIONAL_ATTRIBUTES,
                'valueHandler' => $attributeValueHandler,
                'defaultUseValue' => [],
                'label' => __('Additional Attributes'),
                'sortOrder' => $sortOrder += 10,
            ]
        );

        $fields[] = $this->fieldFactory->create(
            Checkbox::TYPE_CODE,
            [
                'name' => self::KEY_EXPORT_ATTRIBUTE_SET_NAME,
                'label' => __('Export Attribute Set Name'),
                'sortOrder' => $sortOrder += 10,
            ]
        );

        return array_merge($fields, parent::getBaseFields());
    }

    public function getFieldsetLabel()
    {
        return __('Feed - Attributes Section');
    }

    public function shouldUseProductIdForSku(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_USE_PRODUCT_ID_FOR_SKU);
    }

    public function getBrandAttribute(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_BRAND_ATTRIBUTE);
    }

    public function getDescriptionAttribute(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_DESCRIPTION_ATTRIBUTE);
    }

    public function getShortDescriptionAttribute(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_SHORT_DESCRIPTION_ATTRIBUTE);
    }

    public function getGtinAttribute(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_GTIN_ATTRIBUTE);
    }

    public function getAttributeMap(StoreInterface $store)
    {
        $attributeMap = [];

        foreach ($this->mappableAttributes as $attributeCode => $attributeLabel) {
            $fieldName = sprintf(self::KEY_BASE_MAPPED_ATTRIBUTE, $attributeCode);
            $attributeMap[$attributeCode] = $this->getFieldValue($store, $fieldName);
        }

        $additionalAttributes = $this->getFieldValue($store, self::KEY_ADDITIONAL_ATTRIBUTES);

        /** @var AbstractAttribute $attribute */
        foreach ($additionalAttributes as $attribute) {
            $attributeCode = $attribute->getAttributeCode();

            if (!isset($attributeMap[$attributeCode])) {
                $attributeMap[$attributeCode] = $attribute;
            }
        }

        return array_filter($attributeMap);
    }

    public function getAllAttributes(StoreInterface $store)
    {
        return array_values(
            array_filter(
                array_merge(
                    [
                        $this->getBrandAttribute($store),
                        $this->getDescriptionAttribute($store),
                        $this->getShortDescriptionAttribute($store),
                        $this->getGtinAttribute($store),
                    ],
                    $this->getAttributeMap($store)
                )
            )
        );
    }

    public function shouldExportAttributeSetName(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_EXPORT_ATTRIBUTE_SET_NAME);
    }
}
