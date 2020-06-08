<?php

namespace ShoppingFeed\Manager\Model\Sales\Order\Customer;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\Data\CustomerInterface;
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
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Math\Random as RandomGenerator;
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
     * @var RandomGenerator
     */
    private $randomGenerator;

    /**
     * @var StringHelper
     */
    private $stringHelper;

    /**
     * @var RegionCollectionFactory
     */
    private $regionCollectionFactory;

    /**
     * @var DirectoryHelper
     */
    private $directoryHelper;

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
     * @param RandomGenerator $randomGenerator
     * @param StringHelper $stringHelper
     * @param RegionCollectionFactory $regionCollectionFactory
     * @param DirectoryHelper $directoryHelper
     * @param CustomerFactory $customerFactory
     * @param CustomerResourceFactory $customerResourceFactory
     * @param CustomerRegistry $customerRegistry
     * @param CustomerAddressFactory $customerAddressFactory
     * @param CustomerAddressResourceFactory $customerAddresssResourceFactory
     * @param OrderConfigInterface $orderGeneralConfig
     */
    public function __construct(
        RandomGenerator $randomGenerator,
        StringHelper $stringHelper,
        RegionCollectionFactory $regionCollectionFactory,
        DirectoryHelper $directoryHelper,
        CustomerFactory $customerFactory,
        CustomerResourceFactory $customerResourceFactory,
        CustomerRegistry $customerRegistry,
        CustomerAddressFactory $customerAddressFactory,
        CustomerAddressResourceFactory $customerAddresssResourceFactory,
        OrderConfigInterface $orderGeneralConfig
    ) {
        $this->randomGenerator = $randomGenerator;
        $this->stringHelper = $stringHelper;
        $this->regionCollectionFactory = $regionCollectionFactory;
        $this->directoryHelper = $directoryHelper;
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
        return ('' !== trim($marketplaceValue))
            ? $marketplaceValue
            : $this->orderGeneralConfig->getAddressFieldPlaceholder($store);
    }

    /**
     * @param MarketplaceAddressInterface $address
     * @param StoreInterface $store
     * @return string
     */
    public function getAddressFirstname(MarketplaceAddressInterface $address, StoreInterface $store)
    {
        return $this->getAddressRequiredFieldValue($address->getFirstName(), $store);
    }

    /**
     * @param MarketplaceAddressInterface $address
     * @param StoreInterface $store
     * @return string
     */
    public function getAddressLastname(MarketplaceAddressInterface $address, StoreInterface $store)
    {
        return $this->getAddressRequiredFieldValue($address->getLastName(), $store);
    }

    /**
     * @param MarketplaceAddressInterface $address
     * @param StoreInterface $store
     * @return string
     */
    public function getAddressCompany(MarketplaceAddressInterface $address, StoreInterface $store)
    {
        return $address->getCompany();
    }

    /**
     * @param MarketplaceAddressInterface $address
     * @param StoreInterface $store
     * @return string
     */
    public function getAddressStreet(MarketplaceAddressInterface $address, StoreInterface $store)
    {
        return $this->getAddressRequiredFieldValue($address->getStreet(), $store);
    }

    /**
     * @param MarketplaceAddressInterface $address
     * @param StoreInterface $store
     * @return string
     */
    public function getAddressPostalCode(MarketplaceAddressInterface $address, StoreInterface $store)
    {
        return $this->getAddressRequiredFieldValue($address->getPostalCode(), $store);
    }

    /**
     * @param MarketplaceAddressInterface $address
     * @param StoreInterface $store
     * @return string
     */
    public function getAddressCity(MarketplaceAddressInterface $address, StoreInterface $store)
    {
        return $this->getAddressRequiredFieldValue($address->getCity(), $store);
    }

    /**
     * @param MarketplaceAddressInterface $address
     * @param StoreInterface $store
     * @return int|null
     */
    public function getAddressRegionId(MarketplaceAddressInterface $address, StoreInterface $store)
    {
        $countryId = $this->getAddressCountryCode($address, $store);
        $regionId = null;
        $regionCode = null;

        if ('FR' === $countryId) {
            $postalCode = $this->getAddressPostalCode($address, $store);
            $regionCode = $this->stringHelper->substr($postalCode, 0, 2);
        } elseif (in_array($countryId, [ 'CA', 'US' ], true)) {
            $streetParts = explode("\n", $this->getAddressStreet($address, $store));
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
     * @param MarketplaceAddressInterface $address
     * @param StoreInterface $store
     * @return string
     */
    public function getAddressCountryCode(MarketplaceAddressInterface $address, StoreInterface $store)
    {
        return $address->getCountryCode();
    }

    /**
     * @param MarketplaceAddressInterface $address
     * @param StoreInterface $store
     * @return string
     */
    public function getAddressEmail(MarketplaceAddressInterface $address, StoreInterface $store)
    {
        $email = $address->getEmail();
        return ('' !== $email) ? $email : $this->orderGeneralConfig->getDefaultEmailAddress($store);
    }

    /**
     * @param MarketplaceAddressInterface $address
     * @param StoreInterface $store
     * @return string
     */
    public function getAddressPhone(MarketplaceAddressInterface $address, StoreInterface $store)
    {
        $phone = $address->getPhone();
        $mobilePhone = $address->getMobilePhone();

        if ((('' === $phone) || $this->orderGeneralConfig->shouldUseMobilePhoneNumberFirst($store))
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
     * @param MarketplaceOrderInterface $marketplaceOrder
     * @param MarketplaceAddressInterface $marketplaceBillingAddress
     * @param StoreInterface $store
     * @return CustomerInterface
     * @throws LocalizedException
     * @throws \Exception
     */
    public function importQuoteCustomer(
        Quote $quote,
        MarketplaceOrderInterface $marketplaceOrder,
        MarketplaceAddressInterface $marketplaceBillingAddress,
        StoreInterface $store
    ) {
        if (!$this->orderGeneralConfig->shouldImportCustomers($store)) {
            $quote->setCustomerIsGuest(true);
            $quote->setCheckoutMethod(QuoteManagerInterface::METHOD_GUEST);
            return null;
        }

        $customerEmail = $marketplaceBillingAddress->getEmail();
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
            $marketplaceOrder->getMarketplaceName()
        );

        if ((null === $groupId) && !$customer->getId()) {
            throw new LocalizedException(
                __('A default customer group must be chosen when customer import is enabled.')
            );
        }

        $customer->addData(
            [
                'is_active' => true,
                'lastname' => $this->getAddressRequiredFieldValue($marketplaceBillingAddress->getLastName(), $store),
                'firstname' => $this->getAddressRequiredFieldValue($marketplaceBillingAddress->getFirstName(), $store),
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
     * @param MarketplaceAddressInterface $marketplaceAddress
     * @param StoreInterface $store
     * @return QuoteAddressInterface
     * @throws LocalizedException
     */
    public function importCustomerQuoteAddress(
        Quote $quote,
        CustomerInterface $customer,
        MarketplaceAddressInterface $marketplaceAddress,
        StoreInterface $store
    ) {
        $customerAddress = $this->customerAddressFactory->create();
        $countryId = $this->getAddressCountryCode($marketplaceAddress, $store);

        $customerAddress->addData(
            [
                'firstname' => $this->getAddressFirstname($marketplaceAddress, $store),
                'lastname' => $this->getAddressLastname($marketplaceAddress, $store),
                'company' => $this->getAddressCompany($marketplaceAddress, $store),
                'street' => $this->getAddressStreet($marketplaceAddress, $store),
                'postcode' => $this->getAddressPostalCode($marketplaceAddress, $store),
                'city' => $this->getAddressCity($marketplaceAddress, $store),
                'country_id' => $countryId,
                'telephone' => $this->getAddressPhone($marketplaceAddress, $store),
            ]
        );

        if (in_array($countryId, $this->directoryHelper->getCountriesWithStatesRequired(), true)) {
            $customerAddress->setRegionId($this->getAddressRegionId($marketplaceAddress, $store));
        }

        $customerAddress->setCustomerId($customer->getId());
        $this->customerAddressResource->save($customerAddress);

        // Remove the customer from the registry cache, because the cached version does not know about the new address. 
        $this->customerRegistry->remove($customer->getId());

        $quoteAddress = $this->getBaseQuoteAddress($quote, $marketplaceAddress);
        $quoteAddress->setSaveInAddressBook(false);
        $quoteAddress->setSameAsBilling(false);
        $quoteAddress->importCustomerAddressData($customerAddress->getDataModel());
        $quoteAddress->setEmail($this->getAddressEmail($marketplaceAddress, $store));

        return $quoteAddress;
    }

    /**
     * @param Quote $quote
     * @param MarketplaceAddressInterface $marketplaceAddress
     * @param StoreInterface $store
     * @return QuoteAddressInterface
     * @throws LocalizedException
     */
    public function importGuestQuoteAddress(
        Quote $quote,
        MarketplaceAddressInterface $marketplaceAddress,
        StoreInterface $store
    ) {
        $countryId = $this->getAddressCountryCode($marketplaceAddress, $store);

        $quoteAddress = $this->getBaseQuoteAddress($quote, $marketplaceAddress);
        $quoteAddress->setFirstname($this->getAddressFirstname($marketplaceAddress, $store));
        $quoteAddress->setLastname($this->getAddressLastname($marketplaceAddress, $store));
        $quoteAddress->setCompany($this->getAddressCompany($marketplaceAddress, $store));
        $quoteAddress->setStreet($this->getAddressStreet($marketplaceAddress, $store));
        $quoteAddress->setPostcode($this->getAddressPostalCode($marketplaceAddress, $store));
        $quoteAddress->setCity($this->getAddressCity($marketplaceAddress, $store));
        $quoteAddress->setCountryId($countryId);
        $quoteAddress->setEmail($this->getAddressEmail($marketplaceAddress, $store));
        $quoteAddress->setTelephone($this->getAddressPhone($marketplaceAddress, $store));

        if (in_array($countryId, $this->directoryHelper->getCountriesWithStatesRequired(), true)) {
            $quoteAddress->setRegionId($this->getAddressRegionId($marketplaceAddress, $store));
        }

        return $quoteAddress;
    }

    /**
     * @param Quote $quote
     * @param MarketplaceAddressInterface $marketplaceAddress
     * @param StoreInterface $store
     * @return QuoteAddressInterface
     * @throws LocalizedException
     */
    public function importQuoteAddress(
        Quote $quote,
        MarketplaceAddressInterface $marketplaceAddress,
        StoreInterface $store
    ) {
        return !$quote->getCustomerId()
            ? $this->importGuestQuoteAddress($quote, $marketplaceAddress, $store)
            : $this->importCustomerQuoteAddress($quote, $quote->getCustomer(), $marketplaceAddress, $store);
    }
}
