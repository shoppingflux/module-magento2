<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Config;

use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Config\Field\Select;
use ShoppingFeed\Manager\Model\Config\Field\TextBox;
use ShoppingFeed\Manager\Model\Config\FieldFactoryInterface;
use ShoppingFeed\Manager\Model\Config\Value\Handler\PositiveInteger as PositiveIntegerHandler;
use ShoppingFeed\Manager\Model\Config\Value\Handler\PositiveNumber as PositiveNumberHandler;
use ShoppingFeed\Manager\Model\Config\Value\Handler\Text as TextHandler;
use ShoppingFeed\Manager\Model\Config\Value\HandlerFactoryInterface as ValueHandlerFactoryInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractConfig;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\SourceInterface as AttributeSourceInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Config\Value\Handler\Attribute as AttributeHandler;


class Shipping extends AbstractConfig implements ShippingInterface
{
    const KEY_CARRIER_NAME_ATTRIBUTE = 'carrier_name_attribute';
    const KEY_FEES_ATTRIBUTE = 'fees_attribute';
    const KEY_DELAY_ATTRIBUTE = 'delay_attribute';
    const KEY_DEFAULT_CARRIER_NAME = 'default_carrier_name';
    const KEY_DEFAULT_FEES = 'default_fees';
    const KEY_DEFAULT_DELAY = 'default_delay';

    /**
     * @var AttributeSourceInterface
     */
    private $attributeSource;

    /**
     * @param FieldFactoryInterface $fieldFactory
     * @param ValueHandlerFactoryInterface $valueHandlerFactory
     * @param AttributeSourceInterface $attributeSource
     */
    public function __construct(
        FieldFactoryInterface $fieldFactory,
        ValueHandlerFactoryInterface $valueHandlerFactory,
        AttributeSourceInterface $attributeSource
    ) {
        $this->attributeSource = $attributeSource;
        parent::__construct($fieldFactory, $valueHandlerFactory);
    }

    protected function getBaseFields()
    {
        $attributeHandler = $this->valueHandlerFactory->create(
            AttributeHandler::TYPE_CODE,
            [ 'attributeSource' => $this->attributeSource ]
        );

        return array_merge(
            [
                $this->fieldFactory->create(
                    Select::TYPE_CODE,
                    [
                        'name' => self::KEY_CARRIER_NAME_ATTRIBUTE,
                        'valueHandler' => $attributeHandler,
                        'label' => __('Carrier Name Attribute'),
                        'sortOrder' => 10,
                    ]
                ),

                $this->fieldFactory->create(
                    Select::TYPE_CODE,
                    [
                        'name' => self::KEY_FEES_ATTRIBUTE,
                        'valueHandler' => $attributeHandler,
                        'label' => __('Fees Attribute'),
                        'sortOrder' => 20,
                    ]
                ),

                $this->fieldFactory->create(
                    Select::TYPE_CODE,
                    [
                        'name' => self::KEY_DELAY_ATTRIBUTE,
                        'valueHandler' => $attributeHandler,
                        'label' => __('Delay Attribute'),
                        'sortOrder' => 30,
                    ]
                ),

                $this->fieldFactory->create(
                    TextBox::TYPE_CODE,
                    [
                        'name' => self::KEY_DEFAULT_CARRIER_NAME,
                        'valueHandler' => $this->valueHandlerFactory->create(TextHandler::TYPE_CODE),
                        'label' => __('Default Carrier Name'),
                        'sortOrder' => 40,
                    ]
                ),

                $this->fieldFactory->create(
                    TextBox::TYPE_CODE,
                    [
                        'name' => self::KEY_DEFAULT_FEES,
                        'valueHandler' => $this->valueHandlerFactory->create(PositiveNumberHandler::TYPE_CODE),
                        'label' => __('Default Fees'),
                        'sortOrder' => 50,
                    ]
                ),

                $this->fieldFactory->create(
                    TextBox::TYPE_CODE,
                    [
                        'name' => self::KEY_DEFAULT_DELAY,
                        'valueHandler' => $this->valueHandlerFactory->create(PositiveIntegerHandler::TYPE_CODE),
                        'label' => __('Default Delay'),
                        'sortOrder' => 60,
                    ]
                ),
            ],
            parent::getBaseFields()
        );
    }

    public function getFieldsetLabel()
    {
        return __('Feed - Shipping Section');
    }

    public function getCarrierNameAttribute(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_CARRIER_NAME_ATTRIBUTE);
    }

    public function getFeesAttribute(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_FEES_ATTRIBUTE);
    }

    public function getDelayAttribute(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_DELAY_ATTRIBUTE);
    }

    public function getDefaultCarrierName(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_DEFAULT_CARRIER_NAME);
    }

    public function getDefaultFees(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_DEFAULT_FEES);
    }

    public function getDefaultDelay(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_DEFAULT_DELAY);
    }

    public function getAllAttributes(StoreInterface $store)
    {
        return array_filter(
            [
                $this->getCarrierNameAttribute($store),
                $this->getFeesAttribute($store),
                $this->getDelayAttribute($store),
            ]
        );
    }
}
