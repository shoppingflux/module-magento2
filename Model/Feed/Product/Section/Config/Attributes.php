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
use ShoppingFeed\Manager\Model\Feed\Product\Section\Type\Attributes as Type;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\SourceInterface as AttributeSourceInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Config\Value\Handler\Attribute as AttributeHandler;

class Attributes extends AbstractConfig implements AttributesInterface
{
    const KEY_USE_PRODUCT_ID_FOR_SKU = 'use_product_id_for_sku';
    const KEY_BRAND_ATTRIBUTE = 'brand_attribute';
    const KEY_DESCRIPTION_ATTRIBUTE = 'description_attribute';
    const KEY_SHORT_DESCRIPTION_ATTRIBUTE = 'short_description_attribute';
    const KEY_GTIN_ATTRIBUTE = 'gtin_attribute';
    const KEY_WEIGHT_ATTRIBUTE = 'weight_attribute';
    const KEY_BASE_MAPPED_ATTRIBUTE = 'attribute_for_%s';
    const KEY_ADDITIONAL_ATTRIBUTES = 'additional_attributes';
    const KEY_EXPORT_ATTRIBUTE_SET_NAME = 'export_attribute_set_name';
    const KEY_EXPORT_VARIATION_URLS = 'export_variation_urls';

    /**
     * @var AttributeSourceInterface
     */
    private $renderableAttributeSource;

    /**
     * @var string[]
     */
    protected $mappableAttributes;

    /**
     * @param FieldFactoryInterface $fieldFactory
     * @param ValueHandlerFactoryInterface $valueHandlerFactory
     * @param AttributeSourceInterface $renderableAttributeSource
     * @param string[] $mappableAttributes
     */
    public function __construct(
        FieldFactoryInterface $fieldFactory,
        ValueHandlerFactoryInterface $valueHandlerFactory,
        AttributeSourceInterface $renderableAttributeSource,
        array $mappableAttributes = []
    ) {
        $this->renderableAttributeSource = $renderableAttributeSource;
        $this->mappableAttributes = array_filter($mappableAttributes);
        parent::__construct($fieldFactory, $valueHandlerFactory);
    }

    protected function getBaseFields()
    {
        $attributeValueHandler = $this->valueHandlerFactory->create(
            AttributeHandler::TYPE_CODE,
            [ 'attributeSource' => $this->renderableAttributeSource ]
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

            $this->fieldFactory->create(
                Select::TYPE_CODE,
                [
                    'name' => self::KEY_WEIGHT_ATTRIBUTE,
                    'valueHandler' => $attributeValueHandler,
                    'isRequired' => false,
                    'label' => __('Weight Attribute'),
                    'sortOrder' => 60,
                ]
            ),
        ];

        $sortOrder = 70;

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

        $reservedAttributeCodes = array_intersect(
            Type::RESERVED_ATTRIBUTE_CODES,
            array_keys($this->renderableAttributeSource->getAttributesByCode())
        );

        $additionalAttributesComment = implode(
            "\n",
            array_merge(
                [
                    __(
                        'Some attribute codes are reserved. If one of the attributes below is chosen, it will be exported as "[code]_attribute":'
                    ),
                ],
                array_map(
                    function ($code) {
                        return '- ' . $code;
                    },
                    $reservedAttributeCodes
                )
            )
        );

        $fields[] = $this->fieldFactory->create(
            MultiSelect::TYPE_CODE,
            [
                'name' => self::KEY_ADDITIONAL_ATTRIBUTES,
                'valueHandler' => $attributeValueHandler,
                'defaultUseValue' => [],
                'label' => __('Additional Attributes'),
                'notice' => $additionalAttributesComment,
                'sortOrder' => $sortOrder += 10,
            ]
        );

        $fields[] = $this->fieldFactory->create(
            Checkbox::TYPE_CODE,
            [
                'name' => self::KEY_EXPORT_ATTRIBUTE_SET_NAME,
                'label' => __('Export Attribute Set Name'),
                'sortOrder' => $sortOrder += 10,
                'checkedNotice' => __('The name of the attribute sets will be exported as "attribute_set".'),
                'uncheckedNotice' => __('The name of the attribute sets will not be exported.'),
            ]
        );

        $fields[] = $this->fieldFactory->create(
            Checkbox::TYPE_CODE,
            [
                'name' => self::KEY_EXPORT_VARIATION_URLS,
                'label' => __('Export Variation URLs'),
                'sortOrder' => $sortOrder += 10,
                'checkedNotice' => __('Variations will use their own URLs.'),
                'uncheckedNotice' => __('Variations will use the URLs of their parent products.'),
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

    public function getWeightAttribute(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_WEIGHT_ATTRIBUTE);
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
                        $this->getWeightAttribute($store),
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

    public function shouldExportVariationUrls(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_EXPORT_VARIATION_URLS);
    }
}
