<?php

namespace ShoppingFeed\Manager\Model\Sales\Order\Customer;

use Magento\Config\Model\Config\Source\Nooptreq as NooptreqSource;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\Data\AddressInterface as CustomerAddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Helper\Address as CustomerAddressHelper;
use Magento\Customer\Model\AddressFactory as CustomerAddressFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\ResourceModel\Address as CustomerAddressResource;
use Magento\Customer\Model\ResourceModel\AddressFactory as CustomerAddressResourceFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Customer\Model\ResourceModel\CustomerFactory as CustomerResourceFactory;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory as RegionCollectionFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filter\Template as TemplateFilter;
use Magento\Framework\Math\Random as RandomGenerator;
use Magento\Framework\Validator\EmailAddress as EmailAddressValidator;
use Magento\Quote\Api\CartManagementInterface as QuoteManagerInterface;
use Magento\Quote\Api\Data\AddressInterface as QuoteAddressInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Store\Model\StoreManagerInterface as BaseStoreManagerInterface;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\AddressInterface as MarketplaceAddressInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;
use ShoppingFeed\Manager\Model\Sales\Order\ConfigInterface as OrderConfigInterface;
use ShoppingFeed\Manager\Model\StringHelper;

class Importer
{
    const CUSTOMER_FROM_SHOPPING_ATTRIBUTE_CODE = 'from_shopping_feed';

    const COUNTRY_CODE_MAPPING = [
        'UK' => 'GB',
    ];

    /** @see \Magento\Directory\Setup\Patch\Data\InitializeDirectoryData */
    const SPAIN_REGION_PREFIX_TO_CODE_MAPPING = [
        '01' => 'Alava',
        '02' => 'Albacete',
        '03' => 'Alicante',
        '04' => 'Almeria',
        '05' => 'Avila',
        '06' => 'Badajoz',
        '07' => 'Baleares',
        '08' => 'Barcelona',
        '09' => 'Burgos',
        '10' => 'Caceres',
        '11' => 'Cadiz',
        '12' => 'Castellon',
        '13' => 'Ciudad Real',
        '14' => 'Cordoba',
        '15' => 'A Coruсa',
        '16' => 'Cuenca',
        '17' => 'Girona',
        '18' => 'Granada',
        '19' => 'Guadalajara',
        '20' => 'Guipuzcoa',
        '21' => 'Huelva',
        '22' => 'Huesca',
        '23' => 'Jaen',
        '24' => 'Leon',
        '25' => 'Lleida',
        '26' => 'La Rioja',
        '27' => 'Lugo',
        '28' => 'Madrid',
        '29' => 'Malaga',
        '30' => 'Murcia',
        '31' => 'Navarra',
        '32' => 'Ourense',
        '33' => 'Asturias',
        '34' => 'Palencia',
        '35' => 'Las Palmas',
        '36' => 'Pontevedra',
        '37' => 'Salamanca',
        '38' => 'Santa Cruz de Tenerife',
        '39' => 'Cantabria',
        '40' => 'Segovia',
        '41' => 'Sevilla',
        '42' => 'Soria',
        '43' => 'Tarragona',
        '44' => 'Teruel',
        '45' => 'Toledo',
        '46' => 'Valencia',
        '47' => 'Valladolid',
        '48' => 'Vizcaya',
        '49' => 'Zamora',
        '50' => 'Zaragoza',
        '51' => 'Ceuta',
        '52' => 'Melilla',
    ];

    /**
     * Patterns matching a single character that Magento's (>= 2.4.8) customer address validators reject.
     *
     * These mirror the (private) patterns used by the core validators (which expose no public accessor).
     *
     * @see \Magento\Customer\Model\Validator\Name
     * @see \Magento\Customer\Model\Validator\City
     * @see \Magento\Customer\Model\Validator\Street
     * @see \Magento\Customer\Model\Validator\Telephone
     */
    const VALIDATOR_PATTERN_NAME = '/[^\p{L}\p{M}\,\-\_\.\'’`&\s\d]/u';
    const VALIDATOR_PATTERN_CITY = '/[^\p{L}\p{M}\s\-\']/u';
    const VALIDATOR_PATTERN_STREET = '/[^\p{L}\p{M}"\[\]\,\-\.\'’`&\s\d]/u';
    const VALIDATOR_PATTERN_TELEPHONE = '/[^\d\s\+\-\(\)]/u';

    /**
     * Default correspondence table used to replace invalid address characters with a known equivalent.
     */
    const DEFAULT_INVALID_CHARACTER_REPLACEMENTS = [
        '–' => '-',
        '—' => '-',
        '―' => '-',
        '‐' => '-',
        '‑' => '-',
        '‒' => '-',
        '−' => '-',
        '•' => '-',
        '·' => '-',
        '’' => '\'',
        '‘' => '\'',
        '‚' => '\'',
        '`' => '\'',
        '“' => '\'',
        '”' => '\'',
        '„' => '\'',
        '«' => '\'',
        '»' => '\'',
    ];

    /**
     * Default pattern to apply per customer address field, indexed by attribute code.
     *
     * Overridable through dependency injection if the core validators ever change.
     */
    const DEFAULT_ADDRESS_FIELD_INVALID_CHARACTER_PATTERNS = [
        CustomerAddressInterface::FIRSTNAME => self::VALIDATOR_PATTERN_NAME,
        CustomerAddressInterface::LASTNAME => self::VALIDATOR_PATTERN_NAME,
        CustomerAddressInterface::CITY => self::VALIDATOR_PATTERN_CITY,
        CustomerAddressInterface::STREET => self::VALIDATOR_PATTERN_STREET,
        CustomerAddressInterface::TELEPHONE => self::VALIDATOR_PATTERN_TELEPHONE,
    ];

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var RandomGenerator
     */
    private $randomGenerator;

    /**
     * @var StringHelper
     */
    private $stringHelper;

    /**
     * @var TemplateFilter
     */
    private $templateFilter;

    /**
     * @var EmailAddressValidator
     */
    private $emailAddressValidator;

    /**
     * @var RegionCollectionFactory
     */
    private $regionCollectionFactory;

    /**
     * @var DirectoryHelper
     */
    private $directoryHelper;

    /**
     * @var BaseStoreManagerInterface
     */
    private $baseStoreManager;

    /**
     * @var CustomerAddressHelper
     */
    private $customerAddressHelper;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var CustomerResource
     */
    private $customerResource;

    /**
     * @var CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @var CustomerAddressFactory
     */
    private $customerAddressFactory;

    /**
     * @var CustomerAddressResource
     */
    private $customerAddressResource;

    /**
     * @var OrderConfigInterface
     */
    private $orderGeneralConfig;

    /**
     * @var array
     */
    private $spainRegionPrefixToCodeMapping;

    /**
     * @var array
     */
    private $invalidCharacterReplacements;

    /**
     * @var array
     */
    private $addressFieldInvalidCharacterPatterns;

    /**
     * @var string[]|null
     */
    private $existingCountryCodes = null;

    /**
     * @param DataObjectFactory $dataObjectFactory
     * @param RandomGenerator $randomGenerator
     * @param StringHelper $stringHelper
     * @param TemplateFilter $templateFilter
     * @param RegionCollectionFactory $regionCollectionFactory
     * @param DirectoryHelper $directoryHelper
     * @param CustomerAddressHelper $customerAddressHelper
     * @param CustomerFactory $customerFactory
     * @param CustomerResourceFactory $customerResourceFactory
     * @param CustomerRegistry $customerRegistry
     * @param CustomerAddressFactory $customerAddressFactory
     * @param CustomerAddressResourceFactory $customerAddresssResourceFactory
     * @param OrderConfigInterface $orderGeneralConfig
     * @param EmailAddressValidator|null $emailAddressValidator
     * @param BaseStoreManagerInterface|null $baseStoreManager
     * @param array|null $spainRegionPrefixToCodeMapping
     * @param array|null $invalidCharacterReplacements
     * @param array|null $addressFieldInvalidCharacterPatterns
     */
    public function __construct(
        DataObjectFactory $dataObjectFactory,
        RandomGenerator $randomGenerator,
        StringHelper $stringHelper,
        TemplateFilter $templateFilter,
        RegionCollectionFactory $regionCollectionFactory,
        DirectoryHelper $directoryHelper,
        CustomerAddressHelper $customerAddressHelper,
        CustomerFactory $customerFactory,
        CustomerResourceFactory $customerResourceFactory,
        CustomerRegistry $customerRegistry,
        CustomerAddressFactory $customerAddressFactory,
        CustomerAddressResourceFactory $customerAddresssResourceFactory,
        OrderConfigInterface $orderGeneralConfig,
        ?EmailAddressValidator $emailAddressValidator = null,
        ?BaseStoreManagerInterface $baseStoreManager = null,
        $spainRegionPrefixToCodeMapping = null,
        $invalidCharacterReplacements = null,
        $addressFieldInvalidCharacterPatterns = null
    ) {
        $this->dataObjectFactory = $dataObjectFactory;
        $this->randomGenerator = $randomGenerator;
        $this->stringHelper = $stringHelper;
        $this->templateFilter = $templateFilter;
        $this->regionCollectionFactory = $regionCollectionFactory;
        $this->directoryHelper = $directoryHelper;
        $this->customerAddressHelper = $customerAddressHelper;
        $this->customerFactory = $customerFactory;
        $this->customerResource = $customerResourceFactory->create();
        $this->customerRegistry = $customerRegistry;
        $this->customerAddressFactory = $customerAddressFactory;
        $this->customerAddressResource = $customerAddresssResourceFactory->create();
        $this->orderGeneralConfig = $orderGeneralConfig;

        $this->emailAddressValidator = $emailAddressValidator
            ?? ObjectManager::getInstance()->get(EmailAddressValidator::class);

        $this->baseStoreManager = $baseStoreManager
            ?? ObjectManager::getInstance()->get(BaseStoreManagerInterface::class);

        $this->spainRegionPrefixToCodeMapping = self::SPAIN_REGION_PREFIX_TO_CODE_MAPPING;

        if (is_array($spainRegionPrefixToCodeMapping)) {
            foreach ($spainRegionPrefixToCodeMapping as $prefix => $code) {
                $this->spainRegionPrefixToCodeMapping[$prefix] = $code;
            }
        }

        $this->invalidCharacterReplacements = self::DEFAULT_INVALID_CHARACTER_REPLACEMENTS;

        if (is_array($invalidCharacterReplacements)) {
            foreach ($invalidCharacterReplacements as $search => $replacement) {
                $this->invalidCharacterReplacements[$search] = $replacement;
            }
        }

        $this->addressFieldInvalidCharacterPatterns = self::DEFAULT_ADDRESS_FIELD_INVALID_CHARACTER_PATTERNS;

        if (is_array($addressFieldInvalidCharacterPatterns)) {
            foreach ($addressFieldInvalidCharacterPatterns as $fieldType => $pattern) {
                $this->addressFieldInvalidCharacterPatterns[$fieldType] = $pattern;
            }
        }
    }

    /**
     * @param string $marketplaceValue
     * @param StoreInterface $store
     * @return string
     */
    public function getAddressRequiredFieldValue($marketplaceValue, StoreInterface $store)
    {
        return ('' !== trim((string) $marketplaceValue))
            ? $marketplaceValue
            : $this->orderGeneralConfig->getAddressFieldPlaceholder($store);
    }

    /**
     * @param DataObject $object
     * @param StoreInterface $store
     * @param string[]|null $fields  Attribute codes to clean; defaults to every validated field.
     */
    private function cleanAddressFields(DataObject $object, StoreInterface $store, ?array $fields = null)
    {
        if (!$this->orderGeneralConfig->shouldReplaceInvalidAddressChars($store)) {
            return;
        }

        foreach ($fields ?? array_keys($this->addressFieldInvalidCharacterPatterns) as $field) {
            if (!$object->hasData($field)) {
                continue;
            }

            $originalValue = $object->getData($field);
            $cleanedValue = $this->cleanAddressFieldValue($field, $originalValue);

            if (('' === $cleanedValue) && ('' !== trim((string) $originalValue))) {
                $placeholder = (CustomerAddressInterface::TELEPHONE === $field)
                    ? $this->orderGeneralConfig->getDefaultPhoneNumber($store)
                    : $this->orderGeneralConfig->getAddressFieldPlaceholder($store);

                if ($placeholder !== '') {
                    $cleanedValue = $this->cleanAddressFieldValue($field, $placeholder);

                    if ('' === $cleanedValue) {
                        $cleanedValue = '-';
                    }
                }
            }

            $object->setData($field, $cleanedValue);
        }
    }

    /**
     * @param string $fieldType
     * @param string|null $value
     * @return string
     */
    private function cleanAddressFieldValue($fieldType, $value)
    {
        $value = (string) $value;

        if (('' === $value) || !isset($this->addressFieldInvalidCharacterPatterns[$fieldType])) {
            return $value;
        }

        $pattern = $this->addressFieldInvalidCharacterPatterns[$fieldType];
        $replacements = $this->invalidCharacterReplacements;

        $cleanedValue = preg_replace_callback(
            $pattern,
            function (array $matches) use ($replacements) {
                return $replacements[$matches[0]] ?? ' ';
            },
            $value
        );

        // A null result means an error occurred (e.g. malformed UTF-8): leave the value untouched.
        if (null === $cleanedValue) {
            return $value;
        }

        // A replacement taken from the table might itself hold characters that are invalid for this field:
        // turn anything still unexpected into a space to guarantee a valid result.
        $cleanedValue = preg_replace($pattern, ' ', $cleanedValue);

        if (null === $cleanedValue) {
            return $value;
        }

        return trim((string) preg_replace('/\h+/u', ' ', $cleanedValue));
    }

    /**
     * @param MarketplaceOrderInterface $order
     * @param MarketplaceAddressInterface $address
     * @param StoreInterface $store
     * @return string
     */
    public function getAddressFirstname(
        MarketplaceOrderInterface $order,
        MarketplaceAddressInterface $address,
        StoreInterface $store
    ) {
        return $this->getAddressRequiredFieldValue($address->getFirstName(), $store);
    }

    /**
     * @param MarketplaceOrderInterface $order
     * @param MarketplaceAddressInterface $address
     * @param StoreInterface $store
     * @return string
     */
    public function getAddressLastname(
        MarketplaceOrderInterface $order,
        MarketplaceAddressInterface $address,
        StoreInterface $store
    ) {
        return $this->getAddressRequiredFieldValue($address->getLastName(), $store);
    }

    /**
     * @param MarketplaceOrderInterface $order
     * @param MarketplaceAddressInterface $address
     * @param StoreInterface $store
     * @return string
     */
    public function getAddressCompany(
        MarketplaceOrderInterface $order,
        MarketplaceAddressInterface $address,
        StoreInterface $store
    ) {
        $companyFieldState = $this->customerAddressHelper->getConfig('company_show');

        return ($companyFieldState !== NooptreqSource::VALUE_REQUIRED)
            ? $address->getCompany()
            : $this->getAddressRequiredFieldValue($address->getCompany(), $store);
    }

    /**
     * @param MarketplaceOrderInterface $order
     * @param MarketplaceAddressInterface $address
     * @param StoreInterface $store
     * @return string
     */
    public function getAddressStreet(
        MarketplaceOrderInterface $order,
        MarketplaceAddressInterface $address,
        StoreInterface $store
    ) {
        $street = $this->getAddressRequiredFieldValue($address->getStreet(), $store);

        $maximumLineCount = $this->customerAddressHelper->getStreetLines();
        $maximumLineLength = $this->orderGeneralConfig->getAddressMaximumStreetLineLength($store);

        if ($maximumLineLength > 0) {
            $baseLines = explode("\n", $street);
            $splitLines = [];

            foreach ($baseLines as $streetLine) {
                if ($this->stringHelper->strlen($streetLine) > $maximumLineLength) {
                    $splitLines = array_merge(
                        $splitLines,
                        $this->stringHelper->split($streetLine, $maximumLineLength, true, true)
                    );
                } else {
                    $splitLines[] = $streetLine;
                }
            }

            if (count($splitLines) > max($maximumLineCount, count($baseLines))) {
                $splitLines = $this->stringHelper->split(
                    implode('︱', $baseLines),
                    $maximumLineLength,
                    true,
                    false,
                    '[\s︱]'
                );

                foreach ($splitLines as $key => $streetLine) {
                    $splitLines[$key] = trim(preg_replace('/︱+/u', ' - ', $streetLine));
                }
            }

            $street = implode("\n", $splitLines);
        }

        return $street;
    }

    /**
     * @param MarketplaceOrderInterface $order
     * @param MarketplaceAddressInterface $address
     * @param StoreInterface $store
     * @return string|null
     */
    public function getAddressRegionName(
        MarketplaceOrderInterface $order,
        MarketplaceAddressInterface $address,
        StoreInterface $store
    ) {
        return $address->getProvince() ?: null;
    }

    /**
     * @param MarketplaceOrderInterface $order
     * @param MarketplaceAddressInterface $address
     * @param StoreInterface $store
     * @return string
     */
    public function getAddressPostalCode(
        MarketplaceOrderInterface $order,
        MarketplaceAddressInterface $address,
        StoreInterface $store
    ) {
        return $this->getAddressRequiredFieldValue($address->getPostalCode(), $store);
    }

    /**
     * @param MarketplaceOrderInterface $order
     * @param MarketplaceAddressInterface $address
     * @param StoreInterface $store
     * @return string
     */
    public function getAddressCity(
        MarketplaceOrderInterface $order,
        MarketplaceAddressInterface $address,
        StoreInterface $store
    ) {
        return $this->getAddressRequiredFieldValue($address->getCity(), $store);
    }

    /**
     * @param MarketplaceOrderInterface $order
     * @param MarketplaceAddressInterface $address
     * @param StoreInterface $store
     * @return int|null
     */
    public function getAddressRegionId(
        MarketplaceOrderInterface $order,
        MarketplaceAddressInterface $address,
        StoreInterface $store
    ) {
        $countryId = $this->getAddressCountryCode($order, $address, $store);
        $regionId = null;
        $regionCode = null;

        if ('FR' === $countryId) {
            $postalCode = $this->getAddressPostalCode($order, $address, $store);
            $regionCode = $this->stringHelper->substr($postalCode, 0, 2);
        } elseif ('ES' === $countryId) {
            $postalCode = $this->getAddressPostalCode($order, $address, $store);
            $regionCode = $this->stringHelper->substr($postalCode, 0, 2);
            $regionCode = $this->spainRegionPrefixToCodeMapping[$regionCode] ?? null;
        } elseif (in_array($countryId, [ 'CA', 'US' ], true)) {
            $streetParts = explode("\n", $this->getAddressStreet($order, $address, $store));
            $regionCode = trim($streetParts[1] ?? '');

            if (!preg_match('/^[a-z]{2}$/i', $regionCode)) {
                $regionCode = null;
            }
        }

        $regionCollection = $this->regionCollectionFactory->create();
        $regionCollection->addCountryFilter($countryId);

        if (!empty($regionCode)) {
            $regionCollection->addRegionCodeFilter($regionCode);
        }

        if ($regionCollection->getSize() > 0) {
            $regionCollection->setCurPage(1);
            $regionCollection->setPageSize(1);

            /** @var Region $region */
            $region = $regionCollection->getFirstItem();
            $regionId = $region->getId() ? (int) $region->getId() : null;
        }

        return $regionId;
    }

    /**
     * @return string[]
     */
    public function getExistingCountryCodes()
    {
        if (null === $this->existingCountryCodes) {
            $this->existingCountryCodes = $this->directoryHelper
                ->getCountryCollection()
                ->getAllIds();
        }

        return $this->existingCountryCodes;
    }

    /**
     * @param MarketplaceOrderInterface $order
     * @param MarketplaceAddressInterface $address
     * @param StoreInterface $store
     * @return string
     */
    public function getAddressCountryCode(
        MarketplaceOrderInterface $order,
        MarketplaceAddressInterface $address,
        StoreInterface $store
    ) {
        $code = $address->getCountryCode();

        if (
            isset(static::COUNTRY_CODE_MAPPING[$code])
            && !in_array($code, $this->getExistingCountryCodes(), true)
        ) {
            $code = static::COUNTRY_CODE_MAPPING[$code];
        }

        return $code;
    }

    /**
     * @param string $value
     * @return string
     */
    public function getAddressEmailVariableValue($value)
    {
        // Underscores are not allowed in the domain part.
        return str_replace('_', '-', $this->stringHelper->getNormalizedCode((string) $value));
    }

    /**
     * @param MarketplaceOrderInterface $order
     * @param MarketplaceAddressInterface $address
     * @param StoreInterface $store
     * @return string
     */
    public function getAddressEmail(
        MarketplaceOrderInterface $order,
        MarketplaceAddressInterface $address,
        StoreInterface $store
    ) {
        $email = $address->getEmail();
        $marketplace = $order->getMarketplaceName();

        try {
            if (
                $this->orderGeneralConfig->shouldForceDefaultEmailAddressForMarketplace($store, $marketplace)
                || ('' === $email)
                || !$this->emailAddressValidator->isValid($email)
            ) {
                $this->templateFilter->setVariables(
                    array_merge(
                        array_filter(
                            [
                                'billing_email' => $order->getBillingAddress()->getEmail(),
                                'shipping_email' => $order->getShippingAddress()->getEmail(),
                            ],
                            function ($email) {
                                return ('' !== $email)
                                    && $this->emailAddressValidator->isValid($email);
                            }
                        ),
                        array_map(
                            [ $this, 'getAddressEmailVariableValue' ],
                            [
                                'marketplace' => $marketplace,
                                'order_id' => $order->getId(),
                                'order_number' => $order->getMarketplaceOrderNumber(),
                                'payment_method' => $order->getPaymentMethod(),
                            ]
                        ),
                        [
                            'address' => $this->dataObjectFactory->create(
                                [
                                    'data' => array_map(
                                        [ $this, 'getAddressEmailVariableValue' ],
                                        [
                                            'first_name' => $address->getFirstName(),
                                            'last_name' => $address->getLastName(),
                                            'company' => $address->getCompany(),
                                            'country' => $this->getAddressCountryCode($order, $address, $store),
                                        ]
                                    ),
                                ]
                            ),
                        ]
                    )
                );

                $email = $this->templateFilter->filter(
                    $this->orderGeneralConfig->getMarketplaceDefaultEmailAddress(
                        $store,
                        $order->getMarketplaceName()
                    )
                );
            }
        } catch (\Exception $e) {
            $email = '';
        }

        return $email;
    }

    /**
     * @param MarketplaceOrderInterface $order
     * @param MarketplaceAddressInterface $address
     * @param StoreInterface $store
     * @return string
     */
    public function getAddressPhone(
        MarketplaceOrderInterface $order,
        MarketplaceAddressInterface $address,
        StoreInterface $store
    ) {
        $phone = $address->getPhone();
        $mobilePhone = $address->getMobilePhone();

        if (
            (('' === $phone) || $this->orderGeneralConfig->shouldUseMobilePhoneNumberFirst($store))
            && ('' !== $mobilePhone)
        ) {
            return $mobilePhone;
        } elseif ('' !== $phone) {
            return $phone;
        }

        return $this->orderGeneralConfig->getDefaultPhoneNumber($store);
    }

    /**
     * @param MarketplaceOrderInterface $order
     * @param MarketplaceAddressInterface $address
     * @param StoreInterface $store
     * @return string|null
     */
    public function getAddressVatId(
        MarketplaceOrderInterface $order,
        MarketplaceAddressInterface $address,
        StoreInterface $store
    ) {
        $vatId = null;

        if ($address->getType() === MarketplaceAddressInterface::TYPE_BILLING) {
            $vatId = trim(
                (string) $order
                    ->getAdditionalFields()
                    ->getDataByKey(MarketplaceOrderInterface::ADDITIONAL_FIELD_VAT_ID)
            );
        }

        return !empty($vatId) ? $vatId : null;
    }

    /**
     * @param Quote $quote
     * @param MarketplaceOrderInterface $order
     * @param MarketplaceAddressInterface $billingAddress
     * @param StoreInterface $store
     * @return CustomerInterface
     * @throws LocalizedException
     * @throws \Exception
     */
    public function importQuoteCustomer(
        Quote $quote,
        MarketplaceOrderInterface $order,
        MarketplaceAddressInterface $billingAddress,
        StoreInterface $store
    ) {
        if (!$this->orderGeneralConfig->shouldImportCustomers($store)) {
            $quote->setCustomerIsGuest(true);
            $quote->setCheckoutMethod(QuoteManagerInterface::METHOD_GUEST);
            return null;
        }

        $quote->setCustomerIsGuest(false);
        $quote->setCheckoutMethod(Quote::CHECKOUT_METHOD_LOGIN_IN);

        $customerEmail = $this->getAddressEmail($order, $billingAddress, $store);

        $customer = $this->customerFactory->create();
        $customer->setWebsiteId($this->baseStoreManager->getWebsite()->getId());
        $customer->loadByEmail($customerEmail);

        if (!$customer->getId()) {
            $customer->addData(
                [
                    'import_mode' => true,
                    'confirmation' => null,
                    'force_confirmed' => true,
                    'email' => $customerEmail,
                    'store_id' => $this->baseStoreManager->getStore()->getId(),
                    'website_id' => $this->baseStoreManager->getWebsite()->getId(),
                    static::CUSTOMER_FROM_SHOPPING_ATTRIBUTE_CODE => true,
                ]
            );

            $customer->setPassword($this->randomGenerator->getRandomString(12));
        }

        $groupId = $this->orderGeneralConfig->getMarketplaceCustomerGroup(
            $store,
            $order->getMarketplaceName()
        );

        if ((null === $groupId) && !$customer->getId()) {
            throw new LocalizedException(
                __('A default customer group must be chosen when customer import is enabled.')
            );
        }

        $customer->addData(
            [
                'is_active' => true,
                'lastname' => $this->getAddressLastname($order, $billingAddress, $store),
                'firstname' => $this->getAddressFirstname($order, $billingAddress, $store),
            ]
        );

        $customer->setGroupId($groupId);

        if (!$customer->getAttributeSetId()) {
            $customer->setAttributeSetId(CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER);
        }

        // Prevent old addresses from being validated.
        foreach ($customer->getAddressesCollection() as $address) {
            if ($address->getId()) {
                $address->setData('should_ignore_validation', true);
            }
        }

        $this->cleanAddressFields(
            $customer,
            $store,
            [ CustomerAddressInterface::FIRSTNAME, CustomerAddressInterface::LASTNAME ]
        );

        $this->customerResource->save($customer);

        $customer = $customer->getDataModel();
        $quote->setCustomer($customer);

        return $customer;
    }

    /**
     * @param Quote $quote
     * @param MarketplaceAddressInterface $marketplaceAddress
     * @return QuoteAddress
     * @throws LocalizedException
     */
    private function getBaseQuoteAddress(Quote $quote, MarketplaceAddressInterface $marketplaceAddress)
    {
        if ($marketplaceAddress->getType() === MarketplaceAddressInterface::TYPE_BILLING) {
            $quoteAddress = $quote->getBillingAddress();
        } elseif ($marketplaceAddress->getType() === MarketplaceAddressInterface::TYPE_SHIPPING) {
            $quoteAddress = $quote->getShippingAddress();
        } else {
            throw new LocalizedException(__('Invalid address type: %1.', $marketplaceAddress->getType()));
        }

        return $quoteAddress;
    }

    /**
     * @param Quote $quote
     * @param CustomerInterface $customer
     * @param MarketplaceOrderInterface $order
     * @param MarketplaceAddressInterface $address
     * @param StoreInterface $store
     * @return QuoteAddressInterface
     * @throws LocalizedException
     */
    public function importCustomerQuoteAddress(
        Quote $quote,
        CustomerInterface $customer,
        MarketplaceOrderInterface $order,
        MarketplaceAddressInterface $address,
        StoreInterface $store
    ) {
        $customerAddress = $this->customerAddressFactory->create();

        $countryId = $this->getAddressCountryCode($order, $address, $store);

        $customerAddress->addData(
            [
                'firstname' => $this->getAddressFirstname($order, $address, $store),
                'lastname' => $this->getAddressLastname($order, $address, $store),
                'company' => $this->getAddressCompany($order, $address, $store),
                'street' => $this->getAddressStreet($order, $address, $store),
                'region' => $this->getAddressRegionName($order, $address, $store),
                'postcode' => $this->getAddressPostalCode($order, $address, $store),
                'city' => $this->getAddressCity($order, $address, $store),
                'country_id' => $countryId,
                'telephone' => $this->getAddressPhone($order, $address, $store),
            ]
        );

        if (in_array($countryId, $this->directoryHelper->getCountriesWithStatesRequired(), true)) {
            $customerAddress->setRegionId($this->getAddressRegionId($order, $address, $store));
        }

        if ($this->orderGeneralConfig->shouldImportVatId($store)) {
            $customerAddress->setVatId($this->getAddressVatId($order, $address, $store));
        }

        $addressType = $address->getType();

        $hasDefaultAddress = (
            ((MarketplaceAddressInterface::TYPE_BILLING === $addressType) && $customer->getDefaultBilling())
            || ((MarketplaceAddressInterface::TYPE_SHIPPING === $addressType) && $customer->getDefaultShipping())
        );

        $defaultAddressImportMode = $this->orderGeneralConfig->getCustomerDefaultAddressImportMode($store);

        if (
            (OrderConfigInterface::CUSTOMER_DEFAULT_ADDRESS_IMPORT_MODE_ALWAYS === $defaultAddressImportMode)
            || (
                !$hasDefaultAddress
                && (OrderConfigInterface::CUSTOMER_DEFAULT_ADDRESS_IMPORT_MODE_IF_NONE === $defaultAddressImportMode)
            )
        ) {
            if (MarketplaceAddressInterface::TYPE_BILLING === $addressType) {
                $customerAddress->setIsDefaultBilling(true);
            } elseif (MarketplaceAddressInterface::TYPE_SHIPPING === $addressType) {
                $customerAddress->setIsDefaultShipping(true);
            }
        }

        $customerAddress->setCustomerId($customer->getId());

        $this->cleanAddressFields($customerAddress, $store);

        $this->customerAddressResource->save($customerAddress);

        // Remove the customer from the registry cache, because the cached version does not know about the new address.
        $this->customerRegistry->remove($customer->getId());

        $quoteAddress = $this->getBaseQuoteAddress($quote, $address);

        $quoteAddress->setSaveInAddressBook(false);
        $quoteAddress->setSameAsBilling(false);
        $quoteAddress->importCustomerAddressData($customerAddress->getDataModel());

        $quoteAddress->setEmail($this->getAddressEmail($order, $address, $store));

        return $quoteAddress;
    }

    /**
     * @param Quote $quote
     * @param MarketplaceOrderInterface $order
     * @param MarketplaceAddressInterface $address
     * @param StoreInterface $store
     * @return QuoteAddressInterface
     * @throws LocalizedException
     */
    public function importGuestQuoteAddress(
        Quote $quote,
        MarketplaceOrderInterface $order,
        MarketplaceAddressInterface $address,
        StoreInterface $store
    ) {
        $quoteAddress = $this->getBaseQuoteAddress($quote, $address);

        $quoteAddress->setFirstname($this->getAddressFirstname($order, $address, $store));
        $quoteAddress->setLastname($this->getAddressLastname($order, $address, $store));
        $quoteAddress->setCompany($this->getAddressCompany($order, $address, $store));
        $quoteAddress->setStreet($this->getAddressStreet($order, $address, $store));
        $quoteAddress->setRegion($this->getAddressRegionName($order, $address, $store));
        $quoteAddress->setPostcode($this->getAddressPostalCode($order, $address, $store));
        $quoteAddress->setCity($this->getAddressCity($order, $address, $store));
        $quoteAddress->setEmail($this->getAddressEmail($order, $address, $store));
        $quoteAddress->setTelephone($this->getAddressPhone($order, $address, $store));

        $countryId = $this->getAddressCountryCode($order, $address, $store);

        if (in_array($countryId, $this->directoryHelper->getCountriesWithStatesRequired(), true)) {
            $quoteAddress->setRegionId($this->getAddressRegionId($order, $address, $store));
        }

        $quoteAddress->setCountryId($countryId);

        if ($this->orderGeneralConfig->shouldImportVatId($store)) {
            $quoteAddress->setVatId($this->getAddressVatId($order, $address, $store));
        }

        $this->cleanAddressFields($quoteAddress, $store);

        return $quoteAddress;
    }

    /**
     * @param Quote $quote
     * @param MarketplaceOrderInterface $order
     * @param MarketplaceAddressInterface $address
     * @param StoreInterface $store
     * @return QuoteAddressInterface
     * @throws LocalizedException
     */
    public function importQuoteAddress(
        Quote $quote,
        MarketplaceOrderInterface $order,
        MarketplaceAddressInterface $address,
        StoreInterface $store
    ) {
        return !$quote->getCustomerId()
            ? $this->importGuestQuoteAddress($quote, $order, $address, $store)
            : $this->importCustomerQuoteAddress($quote, $quote->getCustomer(), $order, $address, $store);
    }
}
