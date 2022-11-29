<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Config;

use Magento\Directory\Model\Config\Source\Country as CountrySource;
use Magento\Tax\Model\Config as TaxConfig;
use Magento\Ui\Component\Form\Element\DataType\Number as UiNumber;
use Magento\Ui\Component\Form\Element\DataType\Text as UiText;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Account\Store\ConfigManager;
use ShoppingFeed\Manager\Model\Config\Field\Select;
use ShoppingFeed\Manager\Model\Config\FieldFactoryInterface;
use ShoppingFeed\Manager\Model\Config\Value\Handler\Option as OptionHandler;
use ShoppingFeed\Manager\Model\Config\Value\HandlerFactoryInterface as ValueHandlerFactoryInterface;
use ShoppingFeed\Manager\Model\Customer\Group\Source as CustomerGroupSource;
use ShoppingFeed\Manager\Model\Feed\Product\Attribute\Source\Fpt as FptAttributeSource;
use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractConfig;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Config\Value\Handler\Attribute as AttributeHandler;

class Prices extends AbstractConfig implements PricesInterface
{
    const KEY_CUSTOMER_GROUP_ID = 'customer_group_id';
    const KEY_DISCOUNT_EXPORT_MODE = 'discount_export_mode';
    const KEY_CONFIGURABLE_PRODUCT_PRICE_TYPE = 'configurable_product_price_type';
    const KEY_ECOTAX_ATTRIBUTE = 'ecotax_attribute';
    const KEY_ECOTAX_COUNTRY = 'ecotax_country';

    const DEFAULT_TAX_COUNTRY = '__default__';

    /**
     * @var CustomerGroupSource
     */
    private $customerGroupSource;

    /**
     * @var CountrySource
     */
    private $countrySource;

    /**
     * @var FptAttributeSource
     */
    private $fptAttributeSource;

    /**
     * @param FieldFactoryInterface $fieldFactory
     * @param ValueHandlerFactoryInterface $valueHandlerFactory
     * @param CustomerGroupSource $customerGroupSource
     * @param CountrySource $countrySource
     * @param FptAttributeSource $fptAttributeSource
     */
    public function __construct(
        FieldFactoryInterface $fieldFactory,
        ValueHandlerFactoryInterface $valueHandlerFactory,
        CustomerGroupSource $customerGroupSource,
        CountrySource $countrySource,
        FptAttributeSource $fptAttributeSource
    ) {
        $this->customerGroupSource = $customerGroupSource;
        $this->countrySource = $countrySource;
        $this->fptAttributeSource = $fptAttributeSource;
        parent::__construct($fieldFactory, $valueHandlerFactory);
    }

    protected function getBaseFields()
    {
        $customerGroupHandler = $this->valueHandlerFactory->create(
            OptionHandler::TYPE_CODE,
            [
                'dataType' => UiNumber::NAME,
                'optionArray' => $this->customerGroupSource->toOptionArray(),
            ]
        );

        $discountExportModeHandler = $this->valueHandlerFactory->create(
            OptionHandler::TYPE_CODE,
            [
                'dataType' => UiNumber::NAME,
                'optionArray' => [
                    [
                        'label' => __('Price Attribute'),
                        'value' => static::DISCOUNT_EXPORT_MODE_PRICE_ATTRIBUTE,
                    ],
                    [
                        'label' => __('Discount Attribute'),
                        'value' => static::DISCOUNT_EXPORT_MODE_DISCOUNT_ATTRIBUTE,
                    ],

                ],
            ]
        );

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

        $fptAttributeHandler = $this->valueHandlerFactory->create(
            AttributeHandler::TYPE_CODE,
            [ 'attributeSource' => $this->fptAttributeSource ]
        );

        $fptAttributeCodes = array_keys($this->fptAttributeSource->getAttributesByCode());

        $ecotaxCountryOptions = $this->countrySource->toOptionArray(true);

        array_unshift(
            $ecotaxCountryOptions,
            [
                'value' => self::DEFAULT_TAX_COUNTRY,
                'label' => __('Default Tax Country'),
            ]
        );

        $ecotaxCountryHandler = $this->valueHandlerFactory->create(
            OptionHandler::TYPE_CODE,
            [
                'dataType' => UiText::NAME,
                'optionArray' => $ecotaxCountryOptions,
            ]
        );

        return array_merge(
            [
                $this->fieldFactory->create(
                    Select::TYPE_CODE,
                    [
                        'name' => self::KEY_CUSTOMER_GROUP_ID,
                        'valueHandler' => $customerGroupHandler,
                        'label' => __('Use Prices from Customer Group'),
                        'isRequired' => true,
                        'defaultFormValue' => CustomerGroupSource::CUSTOMER_GROUP_ID_NOT_LOGGED_IN,
                        'defaultUseValue' => CustomerGroupSource::CUSTOMER_GROUP_ID_NOT_LOGGED_IN,
                        'sortOrder' => 10,
                    ]
                ),

                $this->fieldFactory->create(
                    Select::TYPE_CODE,
                    [
                        'name' => self::KEY_DISCOUNT_EXPORT_MODE,
                        'valueHandler' => $discountExportModeHandler,
                        'label' => __('Export Discount Prices in'),
                        'isRequired' => true,
                        'defaultFormValue' => static::DISCOUNT_EXPORT_MODE_DISCOUNT_ATTRIBUTE,
                        'defaultUseValue' => static::DISCOUNT_EXPORT_MODE_DISCOUNT_ATTRIBUTE,
                        'notice' => __('Default value is: "%1".', __('Discount Attribute'))
                            . "\n"
                            . __('Do not change this value unless you know what you are doing.'),
                        'sortOrder' => 20,
                    ]
                ),

                $this->fieldFactory->create(
                    Select::TYPE_CODE,
                    [
                        'name' => self::KEY_CONFIGURABLE_PRODUCT_PRICE_TYPE,
                        'valueHandler' => $configurableProductPriceTypeHandler,
                        'label' => __('Configurable Products Price Type'),
                        'isRequired' => true,
                        'defaultFormValue' => static::CONFIGURABLE_PRODUCT_PRICE_TYPE_NONE,
                        'defaultUseValue' => static::CONFIGURABLE_PRODUCT_PRICE_TYPE_NONE,
                        'sortOrder' => 30,
                    ]
                ),

                $this->fieldFactory->create(
                    Select::TYPE_CODE,
                    [
                        'name' => self::KEY_ECOTAX_ATTRIBUTE,
                        'valueHandler' => $fptAttributeHandler,
                        'label' => __('Eco-tax Attribute'),
                        'isRequired' => false,
                        'dependencies' => [
                            [
                                'values' => $fptAttributeCodes,
                                'fieldNames' => [ self::KEY_ECOTAX_COUNTRY ],
                            ],
                        ],
                        'sortOrder' => 40,
                    ]
                ),

                $this->fieldFactory->create(
                    Select::TYPE_CODE,
                    [
                        'name' => self::KEY_ECOTAX_COUNTRY,
                        'valueHandler' => $ecotaxCountryHandler,
                        'label' => __('Use Eco-tax Amount Configured For'),
                        'isRequired' => true,
                        'defaultFormValue' => self::DEFAULT_TAX_COUNTRY,
                        'defaultUseValue' => self::DEFAULT_TAX_COUNTRY,
                        'sortOrder' => 50,
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

    public function getCustomerGroupId(StoreInterface $store)
    {
        return $this->customerGroupSource->getGroupId($this->getFieldValue($store, self::KEY_CUSTOMER_GROUP_ID));
    }

    public function getDiscountExportMode(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_DISCOUNT_EXPORT_MODE);
    }

    public function getConfigurableProductPriceType(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_CONFIGURABLE_PRODUCT_PRICE_TYPE);
    }

    public function getEcotaxAttribute(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_ECOTAX_ATTRIBUTE);
    }

    public function getEcotaxCountry(StoreInterface $store)
    {
        $country = $this->getFieldValue($store, self::KEY_ECOTAX_COUNTRY);

        return (self::DEFAULT_TAX_COUNTRY !== $country)
            ? $country
            : $store->getScopeConfigValue(TaxConfig::CONFIG_XML_PATH_DEFAULT_COUNTRY);
    }

    public function upgradeStoreData(StoreInterface $store, ConfigManager $configManager, $moduleVersion)
    {
        if (version_compare($moduleVersion, '0.30.0', '<')) {
            $this->setFieldValue($store, self::KEY_DISCOUNT_EXPORT_MODE, self::DISCOUNT_EXPORT_MODE_PRICE_ATTRIBUTE);
        }
    }
}
