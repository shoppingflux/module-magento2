<?php

namespace ShoppingFeed\Manager\Model\Sales\Order;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\Information as StoreInformation;
use Magento\Store\Model\ScopeInterface;
use Magento\Ui\Component\Form\Element\DataType\Number as UiNumber;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Config\Field\Checkbox;
use ShoppingFeed\Manager\Model\Config\Field\DynamicRows;
use ShoppingFeed\Manager\Model\Config\Field\Select;
use ShoppingFeed\Manager\Model\Config\Field\TextBox;
use ShoppingFeed\Manager\Model\Config\FieldFactoryInterface;
use ShoppingFeed\Manager\Model\Config\Value\Handler\Email as EmailHandler;
use ShoppingFeed\Manager\Model\Config\Value\Handler\Option as OptionHandler;
use ShoppingFeed\Manager\Model\Config\Value\Handler\Text as TextHandler;
use ShoppingFeed\Manager\Model\Config\Value\HandlerFactoryInterface as ValueHandlerFactoryInterface;
use ShoppingFeed\Manager\Model\Customer\Group\Source as CustomerGroupSource;
use ShoppingFeed\Manager\Model\Marketplace\Source as MarketplaceSource;
use ShoppingFeed\Manager\Model\StringHelper;

class Config extends AbstractConfig implements ConfigInterface
{
    const KEY_USE_ITEM_REFERENCE_AS_PRODUCT_ID = 'use_item_reference_as_product_id';
    const KEY_CHECK_PRODUCT_AVAILABILITY_AND_OPTIONS = 'check_product_availability_and_options';
    const KEY_CHECK_PRODUCT_WEBSITES = 'check_product_websites';
    const KEY_SYNC_NON_IMPORTED_ADDRESSES = 'sync_non_imported_addresses';
    const KEY_IMPORT_CUSTOMERS = 'import_customers';
    const KEY_DEFAULT_CUSTOMER_GROUP = 'default_customer_group';
    const KEY_MARKETPLACE_CUSTOMER_GROUPS = 'marketplace_customer_groups';
    const KEY_MARKETPLACE_CUSTOMER_GROUPS__MARKETPLACE = 'marketplace';
    const KEY_MARKETPLACE_CUSTOMER_GROUPS__CUSTOMER_GROUP = 'customer_group';
    const KEY_DEFAULT_EMAIL_ADDRESS = 'default_email_address';
    const KEY_DEFAULT_PHONE_NUMBER = 'default_phone_number';
    const KEY_ADDRESS_FIELD_PLACEHOLDER = 'address_field_placeholder';
    const KEY_USE_MOBILE_PHONE_NUMBER_FIRST = 'use_mobile_phone_number_first';
    const KEY_FORCE_CROSS_BORDER_TRADE = 'force_border_trade';
    const KEY_CREATE_INVOICE = 'create_invoice';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StringHelper
     */
    private $stringHelper;

    /**
     * @var CustomerGroupSource
     */
    private $customerGroupSource;

    /**
     * @var MarketplaceSource
     */
    private $marketplaceSource;

    /**
     * @param FieldFactoryInterface $fieldFactory
     * @param ValueHandlerFactoryInterface $valueHandlerFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param StringHelper $stringHelper
     * @param CustomerGroupSource $customerGroupSource
     * @param MarketplaceSource $marketplaceSource
     */
    public function __construct(
        FieldFactoryInterface $fieldFactory,
        ValueHandlerFactoryInterface $valueHandlerFactory,
        ScopeConfigInterface $scopeConfig,
        StringHelper $stringHelper,
        CustomerGroupSource $customerGroupSource,
        MarketplaceSource $marketplaceSource
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->stringHelper = $stringHelper;
        $this->customerGroupSource = $customerGroupSource;
        $this->marketplaceSource = $marketplaceSource;
        parent::__construct($fieldFactory, $valueHandlerFactory);
    }

    public function getScopeSubPath()
    {
        return [ 'general' ];
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

        $marketplaceHandler = $this->valueHandlerFactory->create(
            OptionHandler::TYPE_CODE,
            [
                'dataType' => UiNumber::NAME,
                'optionArray' => $this->marketplaceSource->toOptionArray(),
            ]
        );

        return array_merge(
            [
                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_USE_ITEM_REFERENCE_AS_PRODUCT_ID,
                        'isCheckedByDefault' => false,
                        'label' => __('Use Item Reference as Product ID'),
                        'checkedNotice' => __('The item references will be considered to correspond to product IDs.'),
                        'uncheckedNotice' => __(
                            'The item references will be considered to correspond to product SKUs.'
                        ),
                        'sortOrder' => 10,
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_CHECK_PRODUCT_AVAILABILITY_AND_OPTIONS,
                        'isCheckedByDefault' => false,
                        'label' => __('Check Product Availability and Required Options'),
                        'sortOrder' => 20,
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_CHECK_PRODUCT_WEBSITES,
                        'isCheckedByDefault' => false,
                        'label' => __('Check Product Websites'),
                        'sortOrder' => 30,
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_SYNC_NON_IMPORTED_ADDRESSES,
                        'isCheckedByDefault' => true,
                        'label' => __('Synchronize Addresses of Non-Imported Orders with Shopping Feed'),
                        'sortOrder' => 40,
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_IMPORT_CUSTOMERS,
                        'isCheckedByDefault' => false,
                        'label' => __('Import Customers'),
                        'checkedDependentFieldNames' => [
                            self::KEY_DEFAULT_CUSTOMER_GROUP,
                            self::KEY_MARKETPLACE_CUSTOMER_GROUPS,
                        ],
                        'sortOrder' => 50,
                    ]
                ),

                $this->fieldFactory->create(
                    Select::TYPE_CODE,
                    [
                        'name' => self::KEY_DEFAULT_CUSTOMER_GROUP,
                        'valueHandler' => $customerGroupHandler,
                        'isRequired' => true,
                        'label' => __('Default Customer Group'),
                        'sortOrder' => 60,
                    ]
                ),

                $this->fieldFactory->create(
                    DynamicRows::TYPE_CODE,
                    [
                        'name' => self::KEY_MARKETPLACE_CUSTOMER_GROUPS,
                        'label' => __('Marketplace Customer Groups'),
                        'fields' => [
                            $this->fieldFactory->create(
                                Select::TYPE_CODE,
                                [
                                    'name' => self::KEY_MARKETPLACE_CUSTOMER_GROUPS__MARKETPLACE,
                                    'valueHandler' => $marketplaceHandler,
                                    'isRequired' => true,
                                    'label' => __('Marketplace'),
                                    'sortOrder' => 10,
                                ]
                            ),
                            $this->fieldFactory->create(
                                Select::TYPE_CODE,
                                [
                                    'name' => self::KEY_MARKETPLACE_CUSTOMER_GROUPS__CUSTOMER_GROUP,
                                    'valueHandler' => $customerGroupHandler,
                                    'isRequired' => true,
                                    'label' => __('Customer Group'),
                                    'sortOrder' => 20,
                                ]
                            ),
                        ],
                        'sort_order' => 70,
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_USE_MOBILE_PHONE_NUMBER_FIRST,
                        'isCheckedByDefault' => true,
                        'label' => __('Use Mobile Phone Number First (If Available)'),
                        'sortOrder' => 80,
                    ]
                ),

                $this->fieldFactory->create(
                    TextBox::TYPE_CODE,
                    [
                        'name' => self::KEY_ADDRESS_FIELD_PLACEHOLDER,
                        'valueHandler' => $this->valueHandlerFactory->create(TextHandler::TYPE_CODE),
                        'isRequired' => true,
                        'defaultFormValue' => $this->getDefaultAddressFieldPlaceholder(),
                        'defaultUseValue' => $this->getDefaultAddressFieldPlaceholder(),
                        'label' => __('Default Address Field Value'),
                        'notice' => __('This value will be used as the default for other missing required fields.'),
                        'sortOrder' => 110,
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_FORCE_CROSS_BORDER_TRADE,
                        'isCheckedByDefault' => true,
                        'label' => __('Force Cross Border Trade'),
                        'sortOrder' => 120,
                        'checkedNotice' =>
                            __('Prevents amount mismatches due to tax computations using different address rates.')
                            . "\n"
                            . __('Only disable this if you know what you are doing.'),
                        'uncheckedNotice' =>
                            __('Prevents amount mismatches due to tax computations using different address rates.')
                            . "\n"
                            . __('Unless you know what you are doing, this option should probably be enabled.'),
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_CREATE_INVOICE,
                        'isCheckedByDefault' => true,
                        'label' => __('Create Invoice'),
                        'sortOrder' => 130,
                    ]
                ),
            ],
            parent::getBaseFields()
        );
    }

    protected function getStoreFields(StoreInterface $store)
    {
        $defaultEmailNotice = __('This email address will be used when none is available for a given address.')
            . "\n"
            . __(
                'Leave empty to use the "%1" store email address ("%2").',
                __('Sales Representative'),
                $this->geStoreDefaultEmailAddress($store)
            );

        $storePhone = $this->getStoreDefaultPhoneNumber($store);
        $defaultPhoneNotice = __('This phone number will be used when none is available for a given address.') . "\n";

        if ('' !== $storePhone) {
            $defaultPhoneNotice .= __('Leave empty to use the store phone number ("%1").', $storePhone);
        } else {
            $defaultPhoneNotice .= __(
                'Leave empty to use the store phone number (if available), or "%1".',
                $this->getDefaultDefaultPhoneNumber()
            );
        }

        return array_merge(
            [
                $this->fieldFactory->create(
                    TextBox::TYPE_CODE,
                    [
                        'name' => self::KEY_DEFAULT_EMAIL_ADDRESS,
                        'valueHandler' => $this->valueHandlerFactory->create(EmailHandler::TYPE_CODE),
                        'label' => __('Default Email Address'),
                        'notice' => $defaultEmailNotice,
                        'sortOrder' => 90,
                    ]
                ),

                $this->fieldFactory->create(
                    TextBox::TYPE_CODE,
                    [
                        'name' => self::KEY_DEFAULT_PHONE_NUMBER,
                        'valueHandler' => $this->valueHandlerFactory->create(TextHandler::TYPE_CODE),
                        'label' => __('Default Phone Number'),
                        'notice' => $defaultPhoneNotice,
                        'sortOrder' => 100,
                    ]
                ),
            ],
            parent::getStoreFields($store)
        );
    }

    public function shouldUseItemReferenceAsProductId(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_USE_ITEM_REFERENCE_AS_PRODUCT_ID);
    }

    public function shouldCheckProductAvailabilityAndOptions(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_CHECK_PRODUCT_AVAILABILITY_AND_OPTIONS);
    }

    public function shouldCheckProductWebsites(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_CHECK_PRODUCT_WEBSITES);
    }

    public function shouldSyncNonImportedAddresses(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_SYNC_NON_IMPORTED_ADDRESSES);
    }

    public function shouldImportCustomers(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_IMPORT_CUSTOMERS);
    }

    /**
     * @param StoreInterface $store
     * @return int|null
     */
    public function getDefaultCustomerGroup(StoreInterface $store)
    {
        return $this->customerGroupSource->getGroupId($this->getFieldValue($store, self::KEY_DEFAULT_CUSTOMER_GROUP));
    }

    /**
     * @param StoreInterface $store
     * @return array
     */
    public function getMarketplaceCustomerGroups(StoreInterface $store)
    {
        $groups = [];
        $value = $this->getFieldValue($store, self::KEY_MARKETPLACE_CUSTOMER_GROUPS);

        if (is_array($value)) {
            foreach ($value as $group) {
                if (is_array($group)
                    && isset($group[self::KEY_MARKETPLACE_CUSTOMER_GROUPS__MARKETPLACE])
                    && isset($group[self::KEY_MARKETPLACE_CUSTOMER_GROUPS__CUSTOMER_GROUP])
                ) {
                    $groups[$group[self::KEY_MARKETPLACE_CUSTOMER_GROUPS__MARKETPLACE]] =
                        $this->customerGroupSource->getGroupId(
                            $group[self::KEY_MARKETPLACE_CUSTOMER_GROUPS__CUSTOMER_GROUP]
                        );
                }
            }
        }

        return $groups;
    }

    public function getMarketplaceCustomerGroup(StoreInterface $store, $marketplace)
    {
        $marketplace = $this->stringHelper->getNormalizedCode($marketplace);
        $marketplaceGroups = $this->getMarketplaceCustomerGroups($store);

        return isset($marketplaceGroups[$marketplace])
            ? $marketplaceGroups[$marketplace]
            : $this->getDefaultCustomerGroup($store);
    }

    /**
     * @param StoreInterface $store
     * @return string
     */
    public function geStoreDefaultEmailAddress(StoreInterface $store)
    {
        return $this->scopeConfig->getValue(
            'trans_email/ident_sales/email',
            ScopeInterface::SCOPE_STORE,
            $store->getBaseStoreId()
        );
    }

    public function getDefaultEmailAddress(StoreInterface $store)
    {
        $defaultEmail = $this->getFieldValue($store, self::KEY_DEFAULT_EMAIL_ADDRESS);
        return ('' !== $defaultEmail) ? $defaultEmail : $this->geStoreDefaultEmailAddress($store);
    }

    /**
     * @return string
     */
    public function getDefaultDefaultPhoneNumber()
    {
        return '0123456789';
    }

    /**
     * @param StoreInterface $store
     * @return string
     */
    public function getStoreDefaultPhoneNumber(StoreInterface $store)
    {
        return trim(
            $this->scopeConfig->getValue(
                StoreInformation::XML_PATH_STORE_INFO_PHONE,
                ScopeInterface::SCOPE_STORE,
                $store->getBaseStoreId()
            )
        );
    }

    public function getDefaultPhoneNumber(StoreInterface $store)
    {
        $defaultPhone = trim($this->getFieldValue($store, self::KEY_DEFAULT_PHONE_NUMBER));

        if ('' === $defaultPhone) {
            $defaultPhone = $this->getStoreDefaultPhoneNumber($store);

            if ('' === $defaultPhone) {
                $defaultPhone = $this->getDefaultDefaultPhoneNumber();
            }
        }

        return $defaultPhone;
    }

    /**
     * @return string
     */
    public function getDefaultAddressFieldPlaceholder()
    {
        return '__';
    }

    public function getAddressFieldPlaceholder(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_ADDRESS_FIELD_PLACEHOLDER);
    }

    public function shouldUseMobilePhoneNumberFirst(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_USE_MOBILE_PHONE_NUMBER_FIRST);
    }

    public function shouldForceCrossBorderTrade(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_FORCE_CROSS_BORDER_TRADE);
    }

    public function shouldCreateInvoice(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_CREATE_INVOICE);
    }

    public function getFieldsetLabel()
    {
        return __('Orders - General');
    }
}
