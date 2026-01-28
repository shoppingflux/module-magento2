<?php

namespace ShoppingFeed\Manager\Model\Sales\Order;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface as TimezoneInterface;
use Magento\Store\Model\Information as StoreInformation;
use Magento\Store\Model\ScopeInterface;
use Magento\Ui\Component\Form\Element\DataType\Number as UiNumber;
use Magento\Ui\Component\Form\Element\DataType\Text as UiText;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Api\Sales\Invoice\Pdf\ProcessorPoolInterface as InvoicePdfProcessorPoolInterface;
use ShoppingFeed\Manager\Model\Account\Store\ConfigManager;
use ShoppingFeed\Manager\Model\Account\Store\RegistryConstants as StoreRegistryConstants;
use ShoppingFeed\Manager\Model\Config\Field\Checkbox;
use ShoppingFeed\Manager\Model\Config\Field\DynamicRows;
use ShoppingFeed\Manager\Model\Config\Field\MultiSelect;
use ShoppingFeed\Manager\Model\Config\Field\Select;
use ShoppingFeed\Manager\Model\Config\Field\TextBox;
use ShoppingFeed\Manager\Model\Config\FieldFactoryInterface;
use ShoppingFeed\Manager\Model\Config\Source\Order\Status\Complete as OrderCompleteStatusSource;
use ShoppingFeed\Manager\Model\Config\Source\Order\Status\NewStatus as OrderNewStatusSource;
use ShoppingFeed\Manager\Model\Config\Source\Order\Status\Processing as OrderProcessingStatusSource;
use ShoppingFeed\Manager\Model\Config\Value\Handler\Option as OptionHandler;
use ShoppingFeed\Manager\Model\Config\Value\Handler\PositiveInteger as PositiveIntegerHandler;
use ShoppingFeed\Manager\Model\Config\Value\Handler\Text as TextHandler;
use ShoppingFeed\Manager\Model\Config\Value\HandlerFactoryInterface as ValueHandlerFactoryInterface;
use ShoppingFeed\Manager\Model\Config\Value\HandlerInterface as ValueHandlerInterface;
use ShoppingFeed\Manager\Model\Customer\Group\Source as CustomerGroupSource;
use ShoppingFeed\Manager\Model\Marketplace\Order\Syncing\Action\Source as OrderSyncingActionSource;
use ShoppingFeed\Manager\Model\Marketplace\Source as MarketplaceSource;
use ShoppingFeed\Manager\Model\Sales\Order\SyncerInterface as SalesOrderSyncerInterface;
use ShoppingFeed\Manager\Model\StringHelper;

class Config extends AbstractConfig implements ConfigInterface
{
    const KEY_IMPORT_ORDERS = 'import_orders';
    const KEY_ORDER_IMPORT_MODE = 'order_import_mode';
    const KEY_ORDER_IMPORT_DELAY = 'order_import_delay';
    const KEY_USE_ITEM_REFERENCE_AS_PRODUCT_ID = 'use_item_reference_as_product_id';
    const KEY_CHECK_PRODUCT_AVAILABILITY_AND_OPTIONS = 'check_product_availability_and_options';
    const KEY_CHECK_PRODUCT_WEBSITES = 'check_product_websites';
    const KEY_CHECK_ADDRESS_COUNTRIES = 'check_address_countries';
    const KEY_SYNC_NON_IMPORTED_ITEMS = 'sync_non_imported_items';
    const KEY_SYNC_NON_IMPORTED_ADDRESSES = 'sync_non_imported_addresses';
    const KEY_IMPORT_CUSTOMERS = 'import_customers';
    const KEY_DEFAULT_CUSTOMER_GROUP = 'default_customer_group';
    const KEY_MARKETPLACE_CUSTOMER_GROUPS = 'marketplace_customer_groups';
    const KEY_MARKETPLACE_CUSTOMER_GROUPS__MARKETPLACE = 'marketplace';
    const KEY_MARKETPLACE_CUSTOMER_GROUPS__CUSTOMER_GROUP = 'customer_group';
    const KEY_CUSTOMER_DEFAULT_ADDRESS_IMPORT_MODE = 'customer_default_address_import_mode';
    const KEY_DEFAULT_EMAIL_ADDRESS = 'default_email_address';
    const KEY_MARKETPLACE_DEFAULT_EMAIL_ADDRESSES = 'marketplace_default_email_addresses';
    const KEY_MARKETPLACE_DEFAULT_EMAIL_ADDRESSES__MARKETPLACE = 'marketplace';
    const KEY_MARKETPLACE_DEFAULT_EMAIL_ADDRESSES__ADDRESS = 'address';
    const KEY_FORCE_DEFAULT_EMAIL_ADDRESS_FOR_MARKETPLACES = 'force_default_email_address_for_marketplaces';
    const KEY_SPLIT_LAST_NAME_WHEN_EMPTY_FIRST_NAME = 'split_last_name_when_empty_first_name';
    const KEY_DEFAULT_PHONE_NUMBER = 'default_phone_number';
    const KEY_ADDRESS_FIELD_PLACEHOLDER = 'address_field_placeholder';
    const KEY_ADDRESS_MAXIMUM_STREET_LINE_LENGTH = 'address_maximum_street_line_length';
    const KEY_USE_MOBILE_PHONE_NUMBER_FIRST = 'use_mobile_phone_number_first';
    const KEY_DISABLE_TAX_FOR_BUSINESS_ORDERS = 'disable_tax_for_business_orders';
    const KEY_IMPORT_VAT_ID = 'import_vat_id';
    const KEY_DEFAULT_PAYMENT_METHOD_TITLE = 'default_payment_method_title';
    const KEY_MARKETPLACE_PAYMENT_METHOD_TITLES = 'marketplace_payment_method_titles';
    const KEY_MARKETPLACE_PAYMENT_METHOD_TITLES__MARKETPLACE = 'marketplace';
    const KEY_MARKETPLACE_PAYMENT_METHOD_TITLES__TITLE = 'title';
    const KEY_FORCE_CROSS_BORDER_TRADE = 'force_border_trade';
    const KEY_CREATE_INVOICE = 'create_invoice';
    const KEY_IMPORT_FULFILLED_ORDERS = 'import_fulfilled_orders';
    const KEY_CREATE_FULFILMENT_SHIPMENT = 'create_fulfilment_shipment';
    const KEY_IMPORT_SHIPPED_ORDERS = 'import_shipped_orders';
    const KEY_CREATE_SHIPPED_SHIPMENT = 'create_shipped_shipment';
    const KEY_NEW_ORDER_STATUS = 'new_order_status';
    const KEY_PROCESSING_ORDER_STATUS = 'processing_order_status';
    const KEY_COMPLETE_ORDER_STATUS = 'complete_order_status';
    const KEY_SEND_ORDER_EMAIL_FOR_MARKETPLACES = 'send_order_email_for_marketplaces';
    const KEY_SEND_INVOICE_EMAIL_FOR_MARKETPLACES = 'send_invoice_email_for_marketplaces';
    const KEY_UPLOAD_INVOICE_PDF_FOR_MARKETPLACES = 'upload_invoice_pdf_for_marketplaces';
    const KEY_INVOICE_PDF_PROCESSOR_CODE = 'invoice_pdf_processor_code';
    const KEY_INVOICE_PDF_UPLOAD_DELAY = 'invoice_pdf_upload_delay';
    const KEY_ORDER_SYNCING_DELAY = 'order_syncing_delay';
    const KEY_ORDER_REFUSAL_SYNCING_ACTION = 'order_refusal_syncing_action';
    const KEY_ORDER_CANCELLATION_SYNCING_ACTION = 'order_cancellation_syncing_action';
    const KEY_ORDER_REFUND_SYNCING_ACTION = 'order_refund_syncing_action';
    const KEY_SHOULD_SYNC_PARTIAL_SHIPMENTS = 'should_sync_partial_shipments';
    const KEY_SHIPMENT_SYNCING_MAXIMUM_DELAY = 'shipment_syncing_maximum_delay';
    const KEY_SYNC_DELIVERED_ORDERS = 'sync_delivered_orders';
    const KEY_ORDER_DELIVERED_STATUSES = 'order_delivered_statuses';
    const KEY_DELIVERY_SYNCING_MAXIMUM_DELAY = 'delivery_syncing_maximum_delay';
    const KEY_ENABLE_DEBUG_MODE = 'enable_debug_mode';

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StringHelper
     */
    private $stringHelper;

    /**
     * @var TimezoneInterface
     */
    private $localeDate;

    /**
     * @var CustomerGroupSource
     */
    private $customerGroupSource;

    /**
     * @var MarketplaceSource
     */
    private $marketplaceSource;

    /**
     * @var OrderSyncingActionSource
     */
    private $orderSyncingActionSource;

    /**
     * @var OrderNewStatusSource
     */
    private $orderNewStatusSource;

    /**
     * @var OrderProcessingStatusSource
     */
    private $orderProcessingStatusSource;

    /**
     * @var OrderCompleteStatusSource
     */
    private $orderCompleteStatusSource;

    /**
     * @var InvoicePdfProcessorPoolInterface
     */
    private $invoicePdfProcessorPool;

    /**
     * @param FieldFactoryInterface $fieldFactory
     * @param ValueHandlerFactoryInterface $valueHandlerFactory
     * @param Registry $coreRegistry
     * @param ScopeConfigInterface $scopeConfig
     * @param StringHelper $stringHelper
     * @param TimezoneInterface $localeDate
     * @param CustomerGroupSource $customerGroupSource
     * @param MarketplaceSource $marketplaceSource
     * @param OrderSyncingActionSource $orderSyncingActionSource
     * @param OrderCompleteStatusSource|null $orderCompleteStatusSource
     * @param InvoicePdfProcessorPoolInterface|null $invoicePdfProcessorPool
     * @param OrderNewStatusSource|null $orderNewStatusSource
     * @param OrderProcessingStatusSource|null $orderProcessingStatusSource
     */
    public function __construct(
        FieldFactoryInterface $fieldFactory,
        ValueHandlerFactoryInterface $valueHandlerFactory,
        Registry $coreRegistry,
        ScopeConfigInterface $scopeConfig,
        StringHelper $stringHelper,
        TimezoneInterface $localeDate,
        CustomerGroupSource $customerGroupSource,
        MarketplaceSource $marketplaceSource,
        OrderSyncingActionSource $orderSyncingActionSource,
        ?OrderCompleteStatusSource $orderCompleteStatusSource = null,
        ?InvoicePdfProcessorPoolInterface $invoicePdfProcessorPool = null,
        ?OrderNewStatusSource $orderNewStatusSource = null,
        ?OrderProcessingStatusSource $orderProcessingStatusSource = null
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->scopeConfig = $scopeConfig;
        $this->stringHelper = $stringHelper;
        $this->localeDate = $localeDate;
        $this->customerGroupSource = $customerGroupSource;
        $this->marketplaceSource = $marketplaceSource;
        $this->orderSyncingActionSource = $orderSyncingActionSource;

        $objectManager = ObjectManager::getInstance();

        $this->orderNewStatusSource = $orderNewStatusSource
            ?? $objectManager->get(OrderNewStatusSource::class);

        $this->orderProcessingStatusSource = $orderProcessingStatusSource
            ?? $objectManager->get(OrderProcessingStatusSource::class);

        $this->orderCompleteStatusSource = $orderCompleteStatusSource
            ?? ObjectManager::getInstance()->get(OrderCompleteStatusSource::class);

        $this->invoicePdfProcessorPool = $invoicePdfProcessorPool
            ?? $objectManager->get(InvoicePdfProcessorPoolInterface::class);

        parent::__construct($fieldFactory, $valueHandlerFactory);
    }

    public function getScopeSubPath()
    {
        return [ 'general' ];
    }

    /**
     * @return ValueHandlerInterface
     */
    private function getInvoicePdfProcessorValueHandler()
    {
        $invoicePdfProcessorOptions = [];

        foreach ($this->invoicePdfProcessorPool->getProcessors() as $processor) {
            if ($processor->isAvailable()) {
                $invoicePdfProcessorOptions[] = [
                    'value' => $processor->getCode(),
                    'label' => $processor->getLabel(),
                ];
            }
        }

        usort(
            $invoicePdfProcessorOptions,
            function ($a, $b) {
                return strnatcasecmp($a['label'], $b['label']);
            }
        );

        return $this->valueHandlerFactory->create(
            OptionHandler::TYPE_CODE,
            [
                'dataType' => UiText::NAME,
                'optionArray' => $invoicePdfProcessorOptions,
            ]
        );
    }

    protected function getBaseFields()
    {
        $orderImportModeHandler = $this->valueHandlerFactory->create(
            OptionHandler::TYPE_CODE,
            [
                'dataType' => UiText::NAME,
                'optionArray' => [
                    [
                        'value' => self::ORDER_IMPORT_MODE_ALL,
                        'label' => __('All'),
                    ],
                    [
                        'value' => self::ORDER_IMPORT_MODE_ONLY_TEST,
                        'label' => __('Test only'),
                    ],
                    [
                        'value' => self::ORDER_IMPORT_MODE_ONLY_LIVE,
                        'label' => __('Live only'),
                    ],
                    [
                        'value' => self::ORDER_IMPORT_MODE_NONE,
                        'label' => __('None'),
                    ],
                ],
            ]
        );

        $customerGroupHandler = $this->valueHandlerFactory->create(
            OptionHandler::TYPE_CODE,
            [
                'dataType' => UiNumber::NAME,
                'optionArray' => $this->customerGroupSource->toOptionArray(),
            ]
        );

        $invoicePdfProcessorHandler = $this->getInvoicePdfProcessorValueHandler();

        $orderSyncingActionHandler = $this->valueHandlerFactory->create(
            OptionHandler::TYPE_CODE,
            [
                'dataType' => UiText::NAME,
                'hasEmptyOption' => true,
                'optionArray' => $this->orderSyncingActionSource->toOptionArray(),
            ]
        );

        $orderNewStatusHandler = $this->valueHandlerFactory->create(
            OptionHandler::TYPE_CODE,
            [
                'dataType' => UiText::NAME,
                'optionArray' => $this->orderNewStatusSource->toOptionArray(),
            ]
        );

        $orderProcessingStatusHandler = $this->valueHandlerFactory->create(
            OptionHandler::TYPE_CODE,
            [
                'dataType' => UiText::NAME,
                'optionArray' => $this->orderProcessingStatusSource->toOptionArray(),
            ]
        );

        $orderCompleteStatusHandler = $this->valueHandlerFactory->create(
            OptionHandler::TYPE_CODE,
            [
                'dataType' => UiText::NAME,
                'optionArray' => $this->orderCompleteStatusSource->toOptionArray(),
            ]
        );

        $textHandler = $this->valueHandlerFactory->create(TextHandler::TYPE_CODE);

        $paymentMethodTitleTemplateVariableNotices = [
            'marketplace' => 'The name of the marketplace.',
            'order_id' => 'The ID of the marketplace order.',
            'order_number' => 'The reference of the order on the marketplace.',
            'payment_method' => 'The payment method that was chosen by the customer.',
        ];

        foreach ($paymentMethodTitleTemplateVariableNotices as $field => &$notice) {
            $notice = '- ' . __('"%1":', $field) . ' ' . __($notice);
        }

        $paymentMethodTitleNotice = implode(
            "\n",
            array_merge(
                [
                    __('Leave empty to use the default title.'),
                    '',
                    __('You can use a template here. The following variables are available:'),
                ],
                $paymentMethodTitleTemplateVariableNotices,
                [
                    '',
                    __(
                        'Example: "Payment on {{var marketplace}}: {{var payment_method}}" could be replaced by "Payment on Amazon: MFN".'
                    ),
                ]
            )
        );

        return array_merge(
            [
                $this->fieldFactory->create(
                    Select::TYPE_CODE,
                    [
                        'name' => self::KEY_ORDER_IMPORT_MODE,
                        'valueHandler' => $orderImportModeHandler,
                        'isRequired' => true,
                        'defaultFormValue' => self::ORDER_IMPORT_MODE_ALL,
                        'defaultUseValue' => self::ORDER_IMPORT_MODE_NONE,
                        'label' => __('Import Orders'),
                        'sortOrder' => 0,
                    ]
                ),

                $this->fieldFactory->create(
                    TextBox::TYPE_CODE,
                    [
                        'name' => self::KEY_ORDER_IMPORT_DELAY,
                        'valueHandler' => $this->valueHandlerFactory->create(PositiveIntegerHandler::TYPE_CODE),
                        'isRequired' => true,
                        'defaultFormValue' => 15,
                        'defaultUseValue' => 15,
                        'label' => __('Import Orders For'),
                        'notice' => __('In days.'),
                        'sortOrder' => 10,
                    ]
                ),

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
                        'sortOrder' => 20,
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_CHECK_PRODUCT_AVAILABILITY_AND_OPTIONS,
                        'isCheckedByDefault' => false,
                        'label' => __('Check Product Availability and Required Options'),
                        'checkedNotice' => __(
                            'Orders containing products that are not available or have required options will not be imported.'
                        ),
                        'uncheckedNotice' => __(
                            'Orders containing products that are not available or have required options will still be imported.'
                        ),
                        'sortOrder' => 30,
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_CHECK_PRODUCT_WEBSITES,
                        'isCheckedByDefault' => false,
                        'label' => __('Check Product Websites'),
                        'checkedNotice' => __(
                            'Orders containing products that are not associated to the right website will not be imported.'
                        ),
                        'uncheckedNotice' => __(
                            'Orders containing products that are not associated to the right website will still be imported.'
                        ),
                        'sortOrder' => 40,
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_CHECK_ADDRESS_COUNTRIES,
                        'isCheckedByDefault' => true,
                        'label' => __('Check Address Countries'),
                        'checkedNotice' => __(
                            'The country of the addresses will be checked against the allowed countries for the website.'
                        ),
                        'uncheckedNotice' => __(
                            'The country of the addresses will not be checked.'
                        ),
                        'sortOrder' => 45,
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_SYNC_NON_IMPORTED_ITEMS,
                        'isCheckedByDefault' => true,
                        'label' => __('Synchronize Items of Non-Imported Orders with Shopping Feed'),
                        'sortOrder' => 50,
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_SYNC_NON_IMPORTED_ADDRESSES,
                        'isCheckedByDefault' => true,
                        'label' => __('Synchronize Addresses of Non-Imported Orders with Shopping Feed'),
                        'sortOrder' => 60,
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_IMPORT_CUSTOMERS,
                        'isCheckedByDefault' => false,
                        'label' => __('Import Customer Accounts'),
                        'checkedNotice' => __(
                            'A customer account will be created for each order, using the billing email address.'
                        ),
                        'uncheckedNotice' => __(
                            'Orders will be imported using guest mode. No customer account will be created.'
                        ),
                        'checkedDependentFieldNames' => [
                            self::KEY_DEFAULT_CUSTOMER_GROUP,
                            self::KEY_MARKETPLACE_CUSTOMER_GROUPS,
                            self::KEY_CUSTOMER_DEFAULT_ADDRESS_IMPORT_MODE,
                        ],
                        'sortOrder' => 70,
                    ]
                ),

                $this->fieldFactory->create(
                    Select::TYPE_CODE,
                    [
                        'name' => self::KEY_DEFAULT_CUSTOMER_GROUP,
                        'valueHandler' => $customerGroupHandler,
                        'isRequired' => true,
                        'label' => __('Default Customer Group'),
                        'sortOrder' => 80,
                    ]
                ),

                // Store fields:
                // * Marketplace Customer Groups
                // * Default Email Address
                // * Marketplace Default Email Addresses
                // * Force Default Email Address For

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_SPLIT_LAST_NAME_WHEN_EMPTY_FIRST_NAME,
                        'isCheckedByDefault' => false,
                        'label' => __('Split Last Name When Empty First Name'),
                        'sortOrder' => 130,
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_USE_MOBILE_PHONE_NUMBER_FIRST,
                        'isCheckedByDefault' => true,
                        'label' => __('Use Mobile Phone Number First (If Available)'),
                        'sortOrder' => 140,
                    ]
                ),

                // Default Phone Number (store field)

                $this->fieldFactory->create(
                    TextBox::TYPE_CODE,
                    [
                        'name' => self::KEY_ADDRESS_FIELD_PLACEHOLDER,
                        'valueHandler' => $textHandler,
                        'isRequired' => true,
                        'defaultFormValue' => $this->getDefaultAddressFieldPlaceholder(),
                        'defaultUseValue' => $this->getDefaultAddressFieldPlaceholder(),
                        'label' => __('Default Address Field Value'),
                        'notice' => __('This value will be used as the default for other missing required fields.'),
                        'sortOrder' => 160,
                    ]
                ),

                $this->fieldFactory->create(
                    TextBox::TYPE_CODE,
                    [
                        'name' => self::KEY_ADDRESS_MAXIMUM_STREET_LINE_LENGTH,
                        'valueHandler' => $this->valueHandlerFactory->create(PositiveIntegerHandler::TYPE_CODE),
                        'defaultFormValue' => null,
                        'defaultUseValue' => null,
                        'label' => __('Maximum Length for Street Lines'),
                        'notice' => __('Leave empty to keep the original street lines.'),
                        'sortOrder' => 170,
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_DISABLE_TAX_FOR_BUSINESS_ORDERS,
                        'isCheckedByDefault' => true,
                        'label' => __('Disable Tax for Business Orders'),
                        'sortOrder' => 180,
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_IMPORT_VAT_ID,
                        'isCheckedByDefault' => false,
                        'label' => __('Import VAT IDs'),
                        'sortOrder' => 190,
                    ]
                ),

                $this->fieldFactory->create(
                    TextBox::TYPE_CODE,
                    [
                        'name' => self::KEY_DEFAULT_PAYMENT_METHOD_TITLE,
                        'valueHandler' => $textHandler,
                        'label' => __('Default Payment Method Title'),
                        'notice' => $paymentMethodTitleNotice,
                        'sortOrder' => 200,
                    ]
                ),

                // Store fields:
                // * Marketplace Payment Method Titles

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_FORCE_CROSS_BORDER_TRADE,
                        'isCheckedByDefault' => true,
                        'label' => __('Force Cross Border Trade'),
                        'checkedNotice' =>
                            __('Prevents amount mismatches due to tax computations using different address rates.')
                            . "\n"
                            . __('Only disable this if you know what you are doing.'),
                        'uncheckedNotice' =>
                            __('Prevents amount mismatches due to tax computations using different address rates.')
                            . "\n"
                            . __('Unless you know what you are doing, this option should probably be enabled.'),
                        'sortOrder' => 220,
                    ]
                ),

                $this->fieldFactory->create(
                    Select::TYPE_CODE,
                    [
                        'name' => self::KEY_NEW_ORDER_STATUS,
                        'valueHandler' => $orderNewStatusHandler,
                        'label' => __('New Order Custom Status'),
                        'notice' => __('Leave empty to use the default status.'),
                        'sortOrder' => 230,
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_CREATE_INVOICE,
                        'isCheckedByDefault' => true,
                        'label' => __('Create Invoice'),
                        'checkedNotice' => __('Orders will be automatically invoiced upon import.'),
                        'uncheckedNotice' => __('Orders will not be invoiced automatically.'),
                        'sortOrder' => 240,
                    ]
                ),

                $this->fieldFactory->create(
                    Select::TYPE_CODE,
                    [
                        'name' => self::KEY_PROCESSING_ORDER_STATUS,
                        'valueHandler' => $orderProcessingStatusHandler,
                        'label' => __('Invoiced Order Custom Status'),
                        'notice' => __('Leave empty to use the default status.'),
                        'sortOrder' => 250,
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_IMPORT_FULFILLED_ORDERS,
                        'isCheckedByDefault' => false,
                        'label' => __('Import Fulfilled Orders'),
                        'sortOrder' => 260,
                        'checkedDependentFieldNames' => [ self::KEY_CREATE_FULFILMENT_SHIPMENT ],
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_CREATE_FULFILMENT_SHIPMENT,
                        'isCheckedByDefault' => true,
                        'label' => __('Create Shipment for Fulfilled Orders'),
                        'checkedNotice' => __(
                            'Orders fulfilled by the marketplaces will be automatically shipped upon import.'
                        ),
                        'uncheckedNotice' => __(
                            'Orders fulfilled by the marketplaces will not be shipped automatically.'
                        ),
                        'sortOrder' => 270,
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_IMPORT_SHIPPED_ORDERS,
                        'isCheckedByDefault' => false,
                        'label' => __('Import Already Shipped Orders'),
                        'checkedDependentFieldNames' => [ self::KEY_CREATE_SHIPPED_SHIPMENT ],
                        'sortOrder' => 280,
                    ]
                ),

                $this->fieldFactory->create(
                    Select::TYPE_CODE,
                    [
                        'name' => self::KEY_COMPLETE_ORDER_STATUS,
                        'valueHandler' => $orderCompleteStatusHandler,
                        'label' => __('Shipped Order Custom Status'),
                        'sortOrder' => 290,
                        'notice' => __('Leave empty to use the default status.'),
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_CREATE_SHIPPED_SHIPMENT,
                        'isCheckedByDefault' => true,
                        'label' => __('Create Shipment for Already Shipped Orders'),
                        'checkedNotice' => __(
                            'Orders already shipped on the marketplaces will be automatically shipped upon import.'
                        ),
                        'uncheckedNotice' => __(
                            'Orders already shipped on the marketplaces will not be shipped automatically.'
                        ),
                        'sortOrder' => 300,
                    ]
                ),

                $this->fieldFactory->create(
                    Select::TYPE_CODE,
                    [
                        'name' => self::KEY_INVOICE_PDF_PROCESSOR_CODE,
                        'valueHandler' => $invoicePdfProcessorHandler,
                        'isRequired' => false,
                        'label' => __('Invoice PDF Processor'),
                        'notice' => __(
                            'Warning: the size of the generated PDF files should not exceed 2 MB. Any files larger than this will be ignored.'
                        ),
                        'sortOrder' => 340,
                    ]
                ),

                $this->fieldFactory->create(
                    TextBox::TYPE_CODE,
                    [
                        'name' => self::KEY_INVOICE_PDF_UPLOAD_DELAY,
                        'valueHandler' => $this->valueHandlerFactory->create(PositiveIntegerHandler::TYPE_CODE),
                        'isRequired' => true,
                        'defaultFormValue' => 15,
                        'defaultUseValue' => 15,
                        'label' => __('Maximum Delay for Uploading Invoice PDF'),
                        'notice' => __(
                            'In days. Only orders imported within this delay will be considered for invoice PDF upload.'
                        ),
                        'sortOrder' => 350,
                    ]
                ),

                $this->fieldFactory->create(
                    TextBox::TYPE_CODE,
                    [
                        'name' => self::KEY_ORDER_SYNCING_DELAY,
                        'valueHandler' => $this->valueHandlerFactory->create(PositiveIntegerHandler::TYPE_CODE),
                        'isRequired' => true,
                        'defaultFormValue' => 15,
                        'defaultUseValue' => 15,
                        'label' => __('Synchronize Imported Orders Canceled on the Marketplaces For'),
                        'notice' => __('In days.'),
                        'sortOrder' => 360,
                    ]
                ),

                $this->fieldFactory->create(
                    Select::TYPE_CODE,
                    [
                        'name' => self::KEY_ORDER_REFUSAL_SYNCING_ACTION,
                        'valueHandler' => $orderSyncingActionHandler,
                        'isRequired' => true,
                        'defaultFormValue' => SalesOrderSyncerInterface::SYNCING_ACTION_NONE,
                        'defaultUseValue' => SalesOrderSyncerInterface::SYNCING_ACTION_NONE,
                        'label' => __('Synchronization Action in Case of Refusal on the Marketplace'),
                        'notice' => __('The action will only be applied if it is compatible with the order state.'),
                        'sortOrder' => 370,
                    ]
                ),

                $this->fieldFactory->create(
                    Select::TYPE_CODE,
                    [
                        'name' => self::KEY_ORDER_CANCELLATION_SYNCING_ACTION,
                        'valueHandler' => $orderSyncingActionHandler,
                        'isRequired' => true,
                        'defaultFormValue' => SalesOrderSyncerInterface::SYNCING_ACTION_NONE,
                        'defaultUseValue' => SalesOrderSyncerInterface::SYNCING_ACTION_NONE,
                        'label' => __('Synchronization Action in Case of Cancellation on the Marketplace'),
                        'notice' => __('The action will only be applied if it is compatible with the order state.'),
                        'sortOrder' => 380,
                    ]
                ),

                $this->fieldFactory->create(
                    Select::TYPE_CODE,
                    [
                        'name' => self::KEY_ORDER_REFUND_SYNCING_ACTION,
                        'valueHandler' => $orderSyncingActionHandler,
                        'isRequired' => true,
                        'defaultFormValue' => SalesOrderSyncerInterface::SYNCING_ACTION_NONE,
                        'defaultUseValue' => SalesOrderSyncerInterface::SYNCING_ACTION_NONE,
                        'label' => __('Synchronization Action in Case of Refund on the Marketplace'),
                        'notice' => __('The action will only be applied if it is compatible with the order state.'),
                        'sortOrder' => 390,
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_SHOULD_SYNC_PARTIAL_SHIPMENTS,
                        'isCheckedByDefault' => true,
                        'label' => __('Synchronize Partial Shipments'),
                        'checkedNotice' => __(
                            'When an order is partially shipped, only the relevant items will be shipped on the marketplace, if possible.'
                        ),
                        'uncheckedNotice' => __(
                            'When an order is partially shipped, the entire order will be shipped on the marketplace.'
                        ),
                        'sortOrder' => 400,
                    ]
                ),

                $this->fieldFactory->create(
                    TextBox::TYPE_CODE,
                    [
                        'name' => self::KEY_SHIPMENT_SYNCING_MAXIMUM_DELAY,
                        'valueHandler' => $this->valueHandlerFactory->create(PositiveIntegerHandler::TYPE_CODE),
                        'isRequired' => true,
                        'defaultFormValue' => 24,
                        'defaultUseValue' => 24,
                        'label' => __('Maximum Delay for Synchronizing Shipments'),
                        'notice' => __(
                            'For each shipment, the module will wait at most that many hours for tracking data to become available, before sending the corresponding update.'
                        ),
                        'sortOrder' => 410,
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_SYNC_DELIVERED_ORDERS,
                        'isCheckedByDefault' => false,
                        'label' => __('Synchronize Delivered Orders'),
                        'sortOrder' => 420,
                        'checkedDependentFieldNames' => [ self::KEY_ORDER_DELIVERED_STATUSES ],
                    ]
                ),

                $this->fieldFactory->create(
                    MultiSelect::TYPE_CODE,
                    [
                        'name' => self::KEY_ORDER_DELIVERED_STATUSES,
                        'valueHandler' => $orderCompleteStatusHandler,
                        'isRequired' => true,
                        'defaultFormValue' => [],
                        'defaultUseValue' => [],
                        'label' => __('Order Delivered Statuses'),
                        'notice' => __('An order is considered delivered if it has one of the selected statuses.'),
                        'sortOrder' => 430,
                    ]
                ),

                $this->fieldFactory->create(
                    TextBox::TYPE_CODE,
                    [
                        'name' => self::KEY_DELIVERY_SYNCING_MAXIMUM_DELAY,
                        'valueHandler' => $this->valueHandlerFactory->create(PositiveIntegerHandler::TYPE_CODE),
                        'isRequired' => true,
                        'defaultFormValue' => 15,
                        'defaultUseValue' => 15,
                        'label' => __('Maximum Delay for Synchronizing Deliveries'),
                        'notice' => __(
                            'In days. Only orders imported within this delay will be considered for delivery synchronization.'
                        ),
                        'sortOrder' => 440,
                    ]
                ),

                $this->fieldFactory->create(
                    Checkbox::TYPE_CODE,
                    [
                        'name' => self::KEY_ENABLE_DEBUG_MODE,
                        'isCheckedByDefault' => false,
                        'label' => __('Enable Debug Mode'),
                        'checkedNotice' => __(
                            'Debug mode is enabled. Debugging data will be logged to "/var/log/sfm_sales_order.log".'
                        ),
                        'uncheckedNotice' => __('Debug mode is disabled.'),
                        'sortOrder' => 450,
                    ]
                ),
            ],
            parent::getBaseFields()
        );
    }

    protected function getStoreFields(StoreInterface $store)
    {
        $oldCurrentStore = $this->coreRegistry->registry(StoreRegistryConstants::CURRENT_ACCOUNT_STORE);

        $this->coreRegistry->unregister(StoreRegistryConstants::CURRENT_ACCOUNT_STORE);
        $this->coreRegistry->register(StoreRegistryConstants::CURRENT_ACCOUNT_STORE, $store);

        $marketplaceHandler = $this->valueHandlerFactory->create(
            OptionHandler::TYPE_CODE,
            [
                'dataType' => UiNumber::NAME,
                'optionArray' => $this->marketplaceSource->toOptionArray(),
            ]
        );

        $this->coreRegistry->unregister(StoreRegistryConstants::CURRENT_ACCOUNT_STORE);
        $this->coreRegistry->register(StoreRegistryConstants::CURRENT_ACCOUNT_STORE, $oldCurrentStore);

        $customerGroupHandler = $this->valueHandlerFactory->create(
            OptionHandler::TYPE_CODE,
            [
                'dataType' => UiNumber::NAME,
                'optionArray' => $this->customerGroupSource->toOptionArray(),
            ]
        );

        $defaultAddressImportModeHandler = $this->valueHandlerFactory->create(
            OptionHandler::TYPE_CODE,
            [
                'dataType' => UiText::NAME,
                'optionArray' => [
                    [
                        'value' => static::CUSTOMER_DEFAULT_ADDRESS_IMPORT_MODE_NEVER,
                        'label' => __('Never'),
                    ],
                    [
                        'value' => static::CUSTOMER_DEFAULT_ADDRESS_IMPORT_MODE_ALWAYS,
                        'label' => __('Always'),
                    ],
                    [
                        'value' => static::CUSTOMER_DEFAULT_ADDRESS_IMPORT_MODE_IF_NONE,
                        'label' => __('If none is set'),
                    ],
                ],
            ]
        );

        $textHandler = $this->valueHandlerFactory->create(TextHandler::TYPE_CODE);

        $emailTemplateVariableNotices = [
            'billing_email' => 'The email address entered in the billing address.',
            'shipping_email' => 'The email address entered in the shipping address.',
            'marketplace' => 'The name of the marketplace.',
            'order_id' => 'The ID of the marketplace order.',
            'order_number' => 'The reference of the order on the marketplace.',
            'payment_method' => 'The payment method that was chosen by the customer.',
            'address.first_name' => 'The first name entered in the billing address.',
            'address.last_name' => 'The last name entered in the billing address.',
            'address.company' => 'The company entered in the billing address.',
            'address.country' => 'The code of the country entered in the billing address.',
        ];

        foreach ($emailTemplateVariableNotices as $field => &$notice) {
            $notice = '- ' . __('"%1":', $field) . ' ' . __($notice);
        }

        $defaultEmailNotice = implode(
            "\n",
            array_merge(
                [
                    __('This email address will be used when none is available for a given address.'),
                    __(
                        'Leave empty to use the "%1" store email address ("%2").',
                        __('Sales Representative'),
                        $this->geStoreDefaultEmailAddress($store)
                    ),
                    '',
                    __('You can use a template here. The following variables are available:'),
                ],
                $emailTemplateVariableNotices,
                [
                    '',
                    __(
                        'Example: "{{depend address.first_name}}{{var address.first_name}}.{{/depend}}{{depend address.last_name}}{{var address.last_name}}.{{/depend}}{{var marketplace}}@test.com" could be replaced by "john.doe.amazon@test.com".'
                    ),
                ]
            )
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
                        'sortOrder' => 90,
                    ]
                ),

                $this->fieldFactory->create(
                    Select::TYPE_CODE,
                    [
                        'name' => self::KEY_CUSTOMER_DEFAULT_ADDRESS_IMPORT_MODE,
                        'valueHandler' => $defaultAddressImportModeHandler,
                        'isRequired' => true,
                        'defaultFormValue' => static::CUSTOMER_DEFAULT_ADDRESS_IMPORT_MODE_NEVER,
                        'defaultUseValue' => static::CUSTOMER_DEFAULT_ADDRESS_IMPORT_MODE_NEVER,
                        'label' => __('Set Imported Addresses as Customer Default Addresses'),
                        'sortOrder' => 100,
                    ]
                ),

                $this->fieldFactory->create(
                    TextBox::TYPE_CODE,
                    [
                        'name' => self::KEY_DEFAULT_EMAIL_ADDRESS,
                        'valueHandler' => $this->valueHandlerFactory->create(TextHandler::TYPE_CODE),
                        'label' => __('Default Email Address'),
                        'notice' => $defaultEmailNotice,
                        'sortOrder' => 110,
                    ]
                ),

                $this->fieldFactory->create(
                    DynamicRows::TYPE_CODE,
                    [
                        'name' => self::KEY_MARKETPLACE_DEFAULT_EMAIL_ADDRESSES,
                        'label' => __('Marketplace Default Email Addresses'),
                        'fields' => [
                            $this->fieldFactory->create(
                                Select::TYPE_CODE,
                                [
                                    'name' => self::KEY_MARKETPLACE_DEFAULT_EMAIL_ADDRESSES__MARKETPLACE,
                                    'valueHandler' => $marketplaceHandler,
                                    'isRequired' => true,
                                    'label' => __('Marketplace'),
                                    'sortOrder' => 10,
                                ]
                            ),
                            $this->fieldFactory->create(
                                TextBox::TYPE_CODE,
                                [
                                    'name' => self::KEY_MARKETPLACE_DEFAULT_EMAIL_ADDRESSES__ADDRESS,
                                    'valueHandler' => $textHandler,
                                    'isRequired' => true,
                                    'label' => __('Email Address'),
                                    'sortOrder' => 20,
                                ]
                            ),
                        ],
                        'sortOrder' => 120,
                    ]
                ),

                $this->fieldFactory->create(
                    MultiSelect::TYPE_CODE,
                    [
                        'name' => self::KEY_FORCE_DEFAULT_EMAIL_ADDRESS_FOR_MARKETPLACES,
                        'valueHandler' => $marketplaceHandler,
                        'allowAll' => true,
                        'defaultUseValue' => [],
                        'label' => __('Force Default Email Address For'),
                        'sortOrder' => 130,
                    ]
                ),

                $this->fieldFactory->create(
                    TextBox::TYPE_CODE,
                    [
                        'name' => self::KEY_DEFAULT_PHONE_NUMBER,
                        'valueHandler' => $this->valueHandlerFactory->create(TextHandler::TYPE_CODE),
                        'label' => __('Default Phone Number'),
                        'notice' => $defaultPhoneNotice,
                        'sortOrder' => 160,
                    ]
                ),

                $this->fieldFactory->create(
                    DynamicRows::TYPE_CODE,
                    [
                        'name' => self::KEY_MARKETPLACE_PAYMENT_METHOD_TITLES,
                        'label' => __('Marketplace Payment Method Titles'),
                        'fields' => [
                            $this->fieldFactory->create(
                                Select::TYPE_CODE,
                                [
                                    'name' => self::KEY_MARKETPLACE_PAYMENT_METHOD_TITLES__MARKETPLACE,
                                    'valueHandler' => $marketplaceHandler,
                                    'isRequired' => true,
                                    'label' => __('Marketplace'),
                                    'sortOrder' => 10,
                                ]
                            ),
                            $this->fieldFactory->create(
                                TextBox::TYPE_CODE,
                                [
                                    'name' => self::KEY_MARKETPLACE_PAYMENT_METHOD_TITLES__TITLE,
                                    'valueHandler' => $textHandler,
                                    'isRequired' => true,
                                    'label' => __('Title'),
                                    'sortOrder' => 20,
                                ]
                            ),
                        ],
                        'sortOrder' => 220,
                    ]
                ),

                $this->fieldFactory->create(
                    MultiSelect::TYPE_CODE,
                    [
                        'name' => self::KEY_SEND_ORDER_EMAIL_FOR_MARKETPLACES,
                        'valueHandler' => $marketplaceHandler,
                        'allowAll' => true,
                        'defaultUseValue' => [],
                        'label' => __('Send Order Email For'),
                        'notice' => __('The email will only be sent if it is enabled in the store configuration.'),
                        'sortOrder' => 310,
                    ]
                ),

                $this->fieldFactory->create(
                    MultiSelect::TYPE_CODE,
                    [
                        'name' => self::KEY_SEND_INVOICE_EMAIL_FOR_MARKETPLACES,
                        'valueHandler' => $marketplaceHandler,
                        'allowAll' => true,
                        'defaultUseValue' => [],
                        'label' => __('Send Invoice Email For'),
                        'notice' => __('The email will only be sent if it is enabled in the store configuration.'),
                        'sortOrder' => 320,
                    ]
                ),

                $this->fieldFactory->create(
                    MultiSelect::TYPE_CODE,
                    [
                        'name' => self::KEY_UPLOAD_INVOICE_PDF_FOR_MARKETPLACES,
                        'valueHandler' => $marketplaceHandler,
                        'allowAll' => true,
                        'defaultUseValue' => [],
                        'label' => __('Upload Invoice PDF For'),
                        'notice' => __(
                            'The invoice PDF will be uploaded to compatible marketplaces only (see list here: https://developer.shopping-feed.com/api/fae0997e4f525-store-order-api#specific-order-operations).'
                        ),
                        'sortOrder' => 330,
                    ]
                ),
            ],
            parent::getStoreFields($store)
        );
    }

    /**
     * @param string $configRowsKey
     * @param string $rowMarketplaceKey
     * @param string $rowValueKey
     * @param StoreInterface $store
     * @param string $marketplace
     * @param mixed $defaultValue
     * @return mixed
     */
    private function getMarketplaceBasedConfigValue(
        $configRowsKey,
        $rowMarketplaceKey,
        $rowValueKey,
        StoreInterface $store,
        $marketplace,
        $defaultValue
    ) {
        $rows = $this->getFieldValue($store, $configRowsKey);
        $marketplace = $this->stringHelper->getNormalizedCode($marketplace);

        if (('' !== $marketplace) && is_array($rows)) {
            foreach ($rows as $row) {
                if (
                    is_array($row)
                    && isset($row[$rowMarketplaceKey])
                    && isset($row[$rowValueKey])
                    && ($row[$rowMarketplaceKey] === $marketplace)
                ) {
                    return $row[$rowValueKey];
                }
            }
        }

        return $defaultValue;
    }

    public function shouldImportOrders(StoreInterface $store)
    {
        return $this->shouldImportTestOrders($store) || $this->shouldImportLiveOrders($store);
    }

    public function getOrderImportMode(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_ORDER_IMPORT_MODE);
    }

    public function shouldImportTestOrders(StoreInterface $store)
    {
        return in_array(
            $this->getOrderImportMode($store),
            [
                static::ORDER_IMPORT_MODE_ALL,
                static::ORDER_IMPORT_MODE_ONLY_TEST,
            ]
        );
    }

    public function shouldImportLiveOrders(StoreInterface $store)
    {
        return in_array(
            $this->getOrderImportMode($store),
            [
                static::ORDER_IMPORT_MODE_ALL,
                static::ORDER_IMPORT_MODE_ONLY_LIVE,
            ]
        );
    }

    public function getOrderImportDelay(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_ORDER_IMPORT_DELAY);
    }

    public function getOrderImportFromDate(StoreInterface $store)
    {
        $fromDate = $this->localeDate->scopeDate($store->getBaseStore());

        return $fromDate->sub(new \DateInterval('P' . $this->getOrderImportDelay($store) . 'D'));
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

    public function shouldCheckAddressCountries(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_CHECK_ADDRESS_COUNTRIES);
    }

    public function shouldSyncNonImportedItems(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_SYNC_NON_IMPORTED_ITEMS);
    }

    public function shouldSyncNonImportedAddresses(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_SYNC_NON_IMPORTED_ADDRESSES);
    }

    public function shouldImportCustomers(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_IMPORT_CUSTOMERS);
    }

    public function getDefaultCustomerGroup(StoreInterface $store)
    {
        return $this->customerGroupSource->getGroupId($this->getFieldValue($store, self::KEY_DEFAULT_CUSTOMER_GROUP));
    }

    public function getMarketplaceCustomerGroup(StoreInterface $store, $marketplace)
    {
        return $this->customerGroupSource->getGroupId(
            $this->getMarketplaceBasedConfigValue(
                static::KEY_MARKETPLACE_CUSTOMER_GROUPS,
                static::KEY_MARKETPLACE_CUSTOMER_GROUPS__MARKETPLACE,
                static::KEY_MARKETPLACE_CUSTOMER_GROUPS__CUSTOMER_GROUP,
                $store,
                $marketplace,
                $this->getFieldValue($store, self::KEY_DEFAULT_CUSTOMER_GROUP)
            )
        );
    }

    public function getCustomerDefaultAddressImportMode(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_CUSTOMER_DEFAULT_ADDRESS_IMPORT_MODE);
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

    public function getMarketplaceDefaultEmailAddress(StoreInterface $store, $marketplace)
    {
        return $this->getMarketplaceBasedConfigValue(
            static::KEY_MARKETPLACE_DEFAULT_EMAIL_ADDRESSES,
            static::KEY_MARKETPLACE_DEFAULT_EMAIL_ADDRESSES__MARKETPLACE,
            static::KEY_MARKETPLACE_DEFAULT_EMAIL_ADDRESSES__ADDRESS,
            $store,
            $marketplace,
            $this->getDefaultEmailAddress($store)
        );
    }

    public function shouldForceDefaultEmailAddressForMarketplace(StoreInterface $store, $marketplace)
    {
        return in_array(
            $this->stringHelper->getNormalizedCode($marketplace),
            (array) $this->getFieldValue($store, self::KEY_FORCE_DEFAULT_EMAIL_ADDRESS_FOR_MARKETPLACES),
            true
        );
    }

    public function shouldSplitLastNameWhenEmptyFirstName(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_SPLIT_LAST_NAME_WHEN_EMPTY_FIRST_NAME);
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
            (string) $this->scopeConfig->getValue(
                StoreInformation::XML_PATH_STORE_INFO_PHONE,
                ScopeInterface::SCOPE_STORE,
                $store->getBaseStoreId()
            )
        );
    }

    public function getDefaultPhoneNumber(StoreInterface $store)
    {
        $defaultPhone = trim((string) $this->getFieldValue($store, self::KEY_DEFAULT_PHONE_NUMBER));

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

    public function getAddressMaximumStreetLineLength(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_ADDRESS_MAXIMUM_STREET_LINE_LENGTH);
    }

    public function shouldUseMobilePhoneNumberFirst(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_USE_MOBILE_PHONE_NUMBER_FIRST);
    }

    public function shouldDisableTaxForBusinessOrders(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_DISABLE_TAX_FOR_BUSINESS_ORDERS);
    }

    public function shouldImportVatId(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_IMPORT_VAT_ID);
    }

    public function getDefaultPaymentMethodTitle(StoreInterface $store)
    {
        return trim((string) $this->getFieldValue($store, self::KEY_DEFAULT_PAYMENT_METHOD_TITLE));
    }

    public function getMarketplacePaymentMethodTitle(StoreInterface $store, $marketplace)
    {
        return trim(
            (string) $this->getMarketplaceBasedConfigValue(
                static::KEY_MARKETPLACE_PAYMENT_METHOD_TITLES,
                static::KEY_MARKETPLACE_PAYMENT_METHOD_TITLES__MARKETPLACE,
                static::KEY_MARKETPLACE_PAYMENT_METHOD_TITLES__TITLE,
                $store,
                $marketplace,
                $this->getDefaultPaymentMethodTitle($store)
            )
        );
    }

    public function shouldForceCrossBorderTrade(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_FORCE_CROSS_BORDER_TRADE);
    }

    public function getNewOrderStatus(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_NEW_ORDER_STATUS);
    }

    public function shouldCreateInvoice(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_CREATE_INVOICE);
    }

    public function getProcessingOrderStatus(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_PROCESSING_ORDER_STATUS);
    }

    public function shouldImportFulfilledOrders(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_IMPORT_FULFILLED_ORDERS);
    }

    public function shouldCreateFulfilmentShipment(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_CREATE_FULFILMENT_SHIPMENT);
    }

    public function shouldImportShippedOrders(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_IMPORT_SHIPPED_ORDERS);
    }

    public function shouldCreateShippedShipment(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_CREATE_SHIPPED_SHIPMENT);
    }

    public function getCompleteOrderStatus(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_COMPLETE_ORDER_STATUS);
    }

    public function shouldSendOrderEmailForMarketplace(StoreInterface $store, $marketplace)
    {
        return in_array(
            $this->stringHelper->getNormalizedCode($marketplace),
            (array) $this->getFieldValue($store, self::KEY_SEND_ORDER_EMAIL_FOR_MARKETPLACES),
            true
        );
    }

    public function shouldSendInvoiceEmailForMarketplace(StoreInterface $store, $marketplace)
    {
        return in_array(
            $this->stringHelper->getNormalizedCode($marketplace),
            (array) $this->getFieldValue($store, self::KEY_SEND_INVOICE_EMAIL_FOR_MARKETPLACES),
            true
        );
    }

    public function isInvoicePdfUploadEnabled(StoreInterface $store)
    {
        return !empty($this->getFieldValue($store, self::KEY_UPLOAD_INVOICE_PDF_FOR_MARKETPLACES));
    }

    public function shouldUploadInvoicePdfForMarketplace(StoreInterface $store, $marketplace)
    {
        return in_array(
            $this->stringHelper->getNormalizedCode($marketplace),
            (array) $this->getFieldValue($store, self::KEY_UPLOAD_INVOICE_PDF_FOR_MARKETPLACES),
            true
        );
    }

    public function getInvoicePdfProcessorCode(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_INVOICE_PDF_PROCESSOR_CODE);
    }

    public function getInvoicePdfUploadDelay(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_INVOICE_PDF_UPLOAD_DELAY);
    }

    public function getInvoicePdfUploadFromDate(StoreInterface $store)
    {
        $fromDate = $this->localeDate->scopeDate($store->getBaseStore());

        return $fromDate->sub(new \DateInterval('P' . $this->getInvoicePdfUploadDelay($store) . 'D'));
    }

    public function getOrderSyncingDelay(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_ORDER_SYNCING_DELAY);
    }

    public function getOrderSyncingFromDate(StoreInterface $store)
    {
        $fromDate = $this->localeDate->scopeDate($store->getBaseStore());

        return $fromDate->sub(new \DateInterval('P' . $this->getOrderSyncingDelay($store) . 'D'));
    }

    public function getOrderRefusalSyncingAction(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_ORDER_REFUSAL_SYNCING_ACTION);
    }

    public function getOrderCancellationSyncingAction(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_ORDER_CANCELLATION_SYNCING_ACTION);
    }

    public function getOrderRefundSyncingAction(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_ORDER_REFUND_SYNCING_ACTION);
    }

    public function shouldSyncPartialShipments(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_SHOULD_SYNC_PARTIAL_SHIPMENTS);
    }

    public function getShipmentSyncingMaximumDelay(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_SHIPMENT_SYNCING_MAXIMUM_DELAY);
    }

    public function shouldSyncDeliveredOrders(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_SYNC_DELIVERED_ORDERS);
    }

    public function getOrderDeliveredStatuses(StoreInterface $store)
    {
        return (array) $this->getFieldValue($store, self::KEY_ORDER_DELIVERED_STATUSES);
    }

    public function getDeliverySyncingMaximumDelay(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_DELIVERY_SYNCING_MAXIMUM_DELAY);
    }

    public function isDebugModeEnabled(StoreInterface $store)
    {
        return $this->getFieldValue($store, self::KEY_ENABLE_DEBUG_MODE);
    }

    public function getFieldsetLabel()
    {
        return __('Orders - General');
    }

    public function upgradeStoreData(StoreInterface $store, ConfigManager $configManager, $moduleVersion)
    {
        if (!$this->hasFieldValue($store, self::KEY_ORDER_IMPORT_MODE)) {
            $this->setFieldValue(
                $store,
                self::KEY_ORDER_IMPORT_MODE,
                $this->getRawFieldValue($store, self::KEY_IMPORT_ORDERS)
                    ? static::ORDER_IMPORT_MODE_ALL
                    : static::ORDER_IMPORT_MODE_NONE
            );
        }
    }
}
