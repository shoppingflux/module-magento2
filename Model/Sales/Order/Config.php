<?php

namespace ShoppingFeed\Manager\Model\Sales\Order;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\Information as StoreInformation;
use Magento\Store\Model\ScopeInterface;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Config\Field\Checkbox;
use ShoppingFeed\Manager\Model\Config\Field\TextBox;
use ShoppingFeed\Manager\Model\Config\FieldFactoryInterface;
use ShoppingFeed\Manager\Model\Config\Value\Handler\Email as EmailHandler;
use ShoppingFeed\Manager\Model\Config\Value\Handler\Text as TextHandler;
use ShoppingFeed\Manager\Model\Config\Value\HandlerFactoryInterface as ValueHandlerFactoryInterface;

class Config extends AbstractConfig implements ConfigInterface
{
    const KEY_USE_ITEM_REFERENCE_AS_PRODUCT_ID = 'use_item_reference_as_product_id';
    const KEY_SYNC_NON_IMPORTED_ADDRESSES = 'sync_non_imported_addresses';
    const KEY_DEFAULT_EMAIL_ADDRESS = 'default_email_address';
    const KEY_DEFAULT_PHONE_NUMBER = 'default_phone_number';
    const KEY_ADDRESS_FIELD_PLACEHOLDER = 'address_field_placeholder';
    const KEY_USE_MOBILE_PHONE_NUMBER_FIRST = 'use_mobile_phone_number_first';
    const KEY_CREATE_INVOICE = 'create_invoice';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param FieldFactoryInterface $fieldFactory
     * @param ValueHandlerFactoryInterface $valueHandlerFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        FieldFactoryInterface $fieldFactory,
        ValueHandlerFactoryInterface $valueHandlerFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($fieldFactory, $valueHandlerFactory);
    }

    public function getScopeSubPath()
    {
        return [ 'general' ];
    }

    protected function getBaseFields()
    {
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
                        'name' => self::KEY_SYNC_NON_IMPORTED_ADDRESSES,
                        'isCheckedByDefault' => true,
                        'label' => __('Synchronize Addresses of Non-Imported Orders with Shopping Feed'),
                        'sortOrder' => 20,
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_USE_MOBILE_PHONE_NUMBER_FIRST,
                        'isCheckedByDefault' => true,
                        'label' => __('Use Mobile Phone Number First (If Available)'),
                        'sortOrder' => 30,
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
                        'sortOrder' => 60,
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_CREATE_INVOICE,
                        'isCheckedByDefault' => true,
                        'label' => __('Create Invoice'),
                        'sortOrder' => 70,
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
                        'sortOrder' => 40,
                    ]
                ),

                $this->fieldFactory->create(
                    TextBox::TYPE_CODE,
                    [
                        'name' => self::KEY_DEFAULT_PHONE_NUMBER,
                        'valueHandler' => $this->valueHandlerFactory->create(TextHandler::TYPE_CODE),
                        'label' => __('Default Phone Number'),
                        'notice' => $defaultPhoneNotice,
                        'sortOrder' => 50,
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

    public function shouldSyncNonImportedAddresses(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_SYNC_NON_IMPORTED_ADDRESSES);
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
        if (('' === ($defaultPhone = $this->getFieldValue($store, self::KEY_DEFAULT_PHONE_NUMBER)))
            && ('' === ($defaultPhone = $this->getStoreDefaultPhoneNumber($store)))
        ) {
            $defaultPhone = $this->getDefaultDefaultPhoneNumber();
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

    public function shouldCreateInvoice(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_CREATE_INVOICE);
    }

    public function getFieldsetLabel()
    {
        return __('Orders - General');
    }
}
