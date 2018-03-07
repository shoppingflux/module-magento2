<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Config;

use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Account\Store\Config\Field\Select;
use ShoppingFeed\Manager\Model\Account\Store\Config\Field\TextBox;
use ShoppingFeed\Manager\Model\Account\Store\Config\Value\Handler\PositiveInteger as PositiveIntegerHandler;
use ShoppingFeed\Manager\Model\Account\Store\Config\Value\Handler\PositiveNumber as PositiveNumberHandler;
use ShoppingFeed\Manager\Model\Account\Store\Config\Value\Handler\Text as TextHandler;
use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractConfig;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Config\Attribute\SourceInterface as AttributeSourceInterface;
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
     * @param AttributeSourceInterface $attributeSource
     */
    public function __construct(AttributeSourceInterface $attributeSource)
    {
        $this->attributeSource = $attributeSource;
    }

    protected function getBaseFields()
    {
        $attributeHandler = new AttributeHandler($this->attributeSource);

        return array_merge(
            [
                new Select(
                    self::KEY_CARRIER_NAME_ATTRIBUTE,
                    $attributeHandler,
                    __('Carrier Name Attribute')
                ),

                new Select(
                    self::KEY_FEES_ATTRIBUTE,
                    $attributeHandler,
                    __('Fees Attribute')
                ),

                new Select(
                    self::KEY_DELAY_ATTRIBUTE,
                    $attributeHandler,
                    __('Delay Attribute')
                ),

                new TextBox(
                    self::KEY_DEFAULT_CARRIER_NAME,
                    new TextHandler(),
                    __('Default Carrier Name')
                ),

                new TextBox(
                    self::KEY_DEFAULT_FEES,
                    new PositiveNumberHandler(),
                    __('Default Fees')
                ),

                new TextBox(
                    self::KEY_DEFAULT_DELAY,
                    new PositiveIntegerHandler(),
                    __('Default Delay')
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
        return $this->getStoreFieldValue($store, self::KEY_CARRIER_NAME_ATTRIBUTE);
    }

    public function getFeesAttribute(StoreInterface $store)
    {
        return $this->getStoreFieldValue($store, self::KEY_FEES_ATTRIBUTE);
    }

    public function getDelayAttribute(StoreInterface $store)
    {
        return $this->getStoreFieldValue($store, self::KEY_DELAY_ATTRIBUTE);
    }

    public function getDefaultCarrierName(StoreInterface $store)
    {
        return $this->getStoreFieldValue($store, self::KEY_DEFAULT_CARRIER_NAME);
    }

    public function getDefaultFees(StoreInterface $store)
    {
        return $this->getStoreFieldValue($store, self::KEY_DEFAULT_FEES);
    }

    public function getDefaultDelay(StoreInterface $store)
    {
        return $this->getStoreFieldValue($store, self::KEY_DEFAULT_DELAY);
    }
}
