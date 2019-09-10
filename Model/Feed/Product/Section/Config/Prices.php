<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Config;

use Magento\Customer\Api\Data\GroupInterface as CustomerGroupInterface;
use Magento\Customer\Model\Customer\Source\Group as CustomerGroupSource;
use Magento\Ui\Component\Form\Element\DataType\Number as UiNumber;
use Magento\Ui\Component\Form\Element\DataType\Text as UiText;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Config\Field\Select;
use ShoppingFeed\Manager\Model\Config\FieldFactoryInterface;
use ShoppingFeed\Manager\Model\Config\Value\Handler\Option as OptionHandler;
use ShoppingFeed\Manager\Model\Config\Value\HandlerFactoryInterface as ValueHandlerFactoryInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractConfig;

class Prices extends AbstractConfig implements PricesInterface
{
    /** @see CustomerGroupInterface::CUST_GROUP_ALL */
    const CUSTOMER_GROUP_ID_NOT_LOGGED_IN = 32001;

    const KEY_CUSTOMER_GROUP_ID = 'customer_group_id';
    const KEY_CONFIGURABLE_PRODUCT_PRICE_TYPE = 'configurable_product_price_type';

    /**
     * @var CustomerGroupSource
     */
    private $customerGroupSource;

    /**
     * @param FieldFactoryInterface $fieldFactory
     * @param ValueHandlerFactoryInterface $valueHandlerFactory
     * @param CustomerGroupSource $customerGroupSource
     */
    public function __construct(
        FieldFactoryInterface $fieldFactory,
        ValueHandlerFactoryInterface $valueHandlerFactory,
        CustomerGroupSource $customerGroupSource
    ) {
        $this->customerGroupSource = $customerGroupSource;
        parent::__construct($fieldFactory, $valueHandlerFactory);
    }

    protected function getBaseFields()
    {
        $allCustomerGroups = $this->customerGroupSource->toOptionArray();
        $customerGroupSource = [];

        foreach ($allCustomerGroups as $customerGroup) {
            if (CustomerGroupInterface::CUST_GROUP_ALL !== $customerGroup['value']) {
                $customerGroupId = (int) $customerGroup['value'];

                if (CustomerGroupInterface::NOT_LOGGED_IN_ID === $customerGroupId) {
                    // Use a positive ID because a value of 0 is not always correctly handled.
                    $customerGroupId = static::CUSTOMER_GROUP_ID_NOT_LOGGED_IN;
                }

                $customerGroupSource[] = array(
                    'value' => $customerGroupId,
                    'label' => trim($customerGroup['label']),
                );
            }
        }

        $customerGroupHandler = $this->valueHandlerFactory->create(
            OptionHandler::TYPE_CODE,
            [
                'dataType' => UiNumber::NAME,
                'optionArray' => $customerGroupSource,
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

        return array_merge(
            [
                $this->fieldFactory->create(
                    Select::TYPE_CODE,
                    [
                        'name' => self::KEY_CUSTOMER_GROUP_ID,
                        'valueHandler' => $customerGroupHandler,
                        'label' => __('Use Prices from Customer Group'),
                        'isRequired' => true,
                        'defaultFormValue' => static::CUSTOMER_GROUP_ID_NOT_LOGGED_IN,
                        'defaultUseValue' => static::CUSTOMER_GROUP_ID_NOT_LOGGED_IN,
                        'sortOrder' => 10,
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
                        'sortOrder' => 20,
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
        $groupId = (int) $this->getFieldValue($store, self::KEY_CUSTOMER_GROUP_ID);

        return (static::CUSTOMER_GROUP_ID_NOT_LOGGED_IN !== $groupId)
            ? $groupId
            : CustomerGroupInterface::NOT_LOGGED_IN_ID;
    }

    public function getConfigurableProductPriceType(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_CONFIGURABLE_PRODUCT_PRICE_TYPE);
    }
}
