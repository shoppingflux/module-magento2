<?php

namespace ShoppingFeed\Manager\Model\Sales\Order\Customer;

use Magento\Customer\Api\CustomerMetadataInterface;
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
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filter\Template as TemplateFilter;
use Magento\Framework\Math\Random as RandomGenerator;
use Magento\Framework\Validator\EmailAddress as EmailAddressValidator;
use Magento\Quote\Api\CartManagementInterface as QuoteManagerInterface;
use Magento\Quote\Api\Data\AddressInterface as QuoteAddressInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\AddressInterface as MarketplaceAddressInterface;
use ShoppingFeed\Manager\Model\Sales\Order\ConfigInterface as OrderConfigInterface;
use ShoppingFeed\Manager\Model\StringHelper;

class Importer
{
    const CUSTOMER_FROM_SHOPPING_ATTRIBUTE_CODE = 'from_shopping_feed';

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
     * @var RegionCollectionFactory
     */
    private $regionCollectionFactory;

    /**
     * @var DirectoryHelper
     */
    private $directoryHelper;

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
        OrderConfigInterface $orderGeneralConfig
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
        return $address->getCompany();
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

        $maximumLineCount = $this->customerAddressHelper->getStreetLines($store->getBaseStore());
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
        return $address->getCountryCode();
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
                || !\Zend_Validate::is($email, EmailAddressValidator::class)
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
                                    && \Zend_Validate::is($email, EmailAddressValidator::class);
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
                                            'country' => $address->getCountryCode(),
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
        $customer->setWebsiteId($store->getBaseWebsiteId());
        $customer->loadByEmail($customerEmail);

        if (!$customer->getId()) {
            $customer->addData(
                [
                    'import_mode' => true,
                    'confirmation' => null,
                    'force_confirmed' => true,
                    'email' => $customerEmail,
                    'store_id' => $store->getBaseStoreId(),
                    'website_id' => $store->getBaseWebsiteId(),
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
                'lastname' => $this->getAddressRequiredFieldValue($billingAddress->getLastName(), $store),
                'firstname' => $this->getAddressRequiredFieldValue($billingAddress->getFirstName(), $store),
            ]
        );

        $customer->setGroupId($groupId);

        if (!$customer->getAttributeSetId()) {
            $customer->setAttributeSetId(CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER);
        }

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
                'postcode' => $this->getAddressPostalCode($order, $address, $store),
                'city' => $this->getAddressCity($order, $address, $store),
                'country_id' => $countryId,
                'telephone' => $this->getAddressPhone($order, $address, $store),
            ]
        );

        if (in_array($countryId, $this->directoryHelper->getCountriesWithStatesRequired(), true)) {
            $customerAddress->setRegionId($this->getAddressRegionId($order, $address, $store));
        }

        $customerAddress->setCustomerId($customer->getId());
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
        $quoteAddress->setPostcode($this->getAddressPostalCode($order, $address, $store));
        $quoteAddress->setCity($this->getAddressCity($order, $address, $store));
        $quoteAddress->setEmail($this->getAddressEmail($order, $address, $store));
        $quoteAddress->setTelephone($this->getAddressPhone($order, $address, $store));

        $countryId = $this->getAddressCountryCode($order, $address, $store);

        if (in_array($countryId, $this->directoryHelper->getCountriesWithStatesRequired(), true)) {
            $quoteAddress->setRegionId($this->getAddressRegionId($order, $address, $store));
        }

        $quoteAddress->setCountryId($countryId);

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
