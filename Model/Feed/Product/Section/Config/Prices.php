<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Config;

use Magento\Ui\Component\Form\Element\DataType\Text as UiText;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Config\Field\Select;
use ShoppingFeed\Manager\Model\Config\Value\Handler\Option as OptionHandler;
use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractConfig;

class Prices extends AbstractConfig implements PricesInterface
{
    const KEY_CONFIGURABLE_PRODUCT_PRICE_TYPE = 'configurable_product_price_type';

    protected function getBaseFields()
    {
        $configurableProductPriceTypeHandler = $this->valueHandlerFactory->create(
            OptionHandler::TYPE_CODE,
            [
                'dataType' => UiText::NAME,
                'optionArray' => [
                    [
                        'label' => __('None'),
                        'value' => static::CONFIGURABLE_PRODUCT_PRICE_TYPE_NONE,
                    ],
                    [
                        'label' => __('Minimum Among Variations'),
                        'value' => static::CONFIGURABLE_PRODUCT_PRICE_TYPE_VARIATIONS_MINIMUM,
                    ],
                    [
                        'label' => __('Maximum Among Variations'),
                        'value' => static::CONFIGURABLE_PRODUCT_PRICE_TYPE_VARIATIONS_MAXIMUM,
                    ],
                ],
            ]
        );

        return array_merge(
            [
                $this->fieldFactory->create(
                    Select::TYPE_CODE,
                    [
                        'name' => self::KEY_CONFIGURABLE_PRODUCT_PRICE_TYPE,
                        'valueHandler' => $configurableProductPriceTypeHandler,
                        'label' => __('Configurable Products Price Type'),
                        'isRequired' => true,
                        'defaultFormValue' => static::CONFIGURABLE_PRODUCT_PRICE_TYPE_NONE,
                        'defaultUseValue' => static::CONFIGURABLE_PRODUCT_PRICE_TYPE_NONE,
                        'sortOrder' => 10,
                    ]
                ),
            ],
            parent::getBaseFields()
        );
    }

    public function getFieldsetLabel()
    {
        return __('Feed - Prices Section');
    }

    public function getConfigurableProductPriceType(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_CONFIGURABLE_PRODUCT_PRICE_TYPE);
    }
}
