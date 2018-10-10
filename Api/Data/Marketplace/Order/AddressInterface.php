<?php

namespace ShoppingFeed\Manager\Api\Data\Marketplace\Order;

/**
 * @api
 */
interface AddressInterface
{
    const TYPE_BILLING = 'billing';
    const TYPE_SHIPPING = 'shipping';

    /**#@+*/
    const ADDRESS_ID = 'address_id';
    const ORDER_ID = 'order_id';
    const TYPE = 'type';
    const FIRST_NAME = 'first_name';
    const LAST_NAME = 'last_name';
    const COMPANY = 'company';
    const STREET = 'street';
    const POSTAL_CODE = 'postal_code';
    const CITY = 'city';
    const COUNTRY_CODE = 'country_code';
    const PHONE = 'phone';
    const MOBILE_PHONE = 'mobile_phone';
    const EMAIL = 'email';
    const MISC_DATA = 'misc_data';
    /**#@+*/

    /**
     * @return int
     */
    public function getId();

    /**
     * @return int
     */
    public function getOrderId();

    /**
     * @return string
     */
    public function getType();

    /**
     * @return string
     */
    public function getFirstName();

    /**
     * @return string
     */
    public function getLastName();

    /**
     * @return string
     */
    public function getCompany();

    /**
     * @return string
     */
    public function getStreet();

    /**
     * @return string
     */
    public function getPostalCode();

    /**
     * @return string
     */
    public function getCity();

    /**
     * @return string
     */
    public function getCountryCode();

    /**
     * @return string
     */
    public function getPhone();

    /**
     * @return string
     */
    public function getMobilePhone();

    /**
     * @return string
     */
    public function getEmail();

    /**
     * @return string
     */
    public function getMiscData();

    /**
     * @param int $orderId
     * @return AddressInterface
     */
    public function setOrderId($orderId);

    /**
     * @param string $type
     * @return AddressInterface
     */
    public function setType($type);

    /**
     * @param string $firstName
     * @return AddressInterface
     */
    public function setFirstName($firstName);

    /**
     * @param string $lastName
     * @return AddressInterface
     */
    public function setLastName($lastName);

    /**
     * @param string $company
     * @return AddressInterface
     */
    public function setCompany($company);

    /**
     * @param string $street
     * @return AddressInterface
     */
    public function setStreet($street);

    /**
     * @param string $postalCode
     * @return AddressInterface
     */
    public function setPostalCode($postalCode);

    /**
     * @param string $city
     * @return AddressInterface
     */
    public function setCity($city);

    /**
     * @param string $countryCode
     * @return AddressInterface
     */
    public function setCountryCode($countryCode);

    /**
     * @param string $phone
     * @return AddressInterface
     */
    public function setPhone($phone);

    /**
     * @param string $phone
     * @return AddressInterface
     */
    public function setMobilePhone($phone);

    /**
     * @param string $email
     * @return AddressInterface
     */
    public function setEmail($email);

    /**
     * @param string $miscData
     * @return AddressInterface
     */
    public function setMiscData($miscData);
}
