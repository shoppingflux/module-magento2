<?php

namespace ShoppingFeed\Manager\Model\Marketplace\Order;

use Magento\Framework\Model\AbstractModel;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\AddressInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Address as AddressResource;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Address\Collection as AddressCollection;

/**
 * @method AddressResource getResource()
 * @method AddressCollection getCollection()
 */
class Address extends AbstractModel implements AddressInterface
{
    protected $_eventPrefix = 'shoppingfeed_manager_marketplace_order_address';
    protected $_eventObject = 'marketplace_order_address';

    protected function _construct()
    {
        $this->_init(AddressResource::class);
    }

    public function getOrderId()
    {
        return (int) $this->getDataByKey(self::ORDER_ID);
    }

    public function getType()
    {
        return trim($this->getDataByKey(self::TYPE));
    }

    public function getFirstName()
    {
        return trim($this->getDataByKey(self::FIRST_NAME));
    }

    public function getLastName()
    {
        return trim($this->getDataByKey(self::LAST_NAME));
    }

    public function getCompany()
    {
        return trim($this->getDataByKey(self::COMPANY));
    }

    public function getStreet()
    {
        return trim($this->getDataByKey(self::STREET));
    }

    public function getPostalCode()
    {
        return trim($this->getDataByKey(self::POSTAL_CODE));
    }

    public function getCity()
    {
        return trim($this->getDataByKey(self::CITY));
    }

    public function getCountryCode()
    {
        return trim($this->getDataByKey(self::COUNTRY_CODE));
    }

    public function getPhone()
    {
        return trim($this->getDataByKey(self::PHONE));
    }

    public function getMobilePhone()
    {
        return trim($this->getDataByKey(self::MOBILE_PHONE));
    }

    public function getEmail()
    {
        return trim($this->getDataByKey(self::EMAIL));
    }

    public function getMiscData()
    {
        return trim($this->getDataByKey(self::MISC_DATA));
    }

    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, (int) $orderId);
    }

    public function setType($type)
    {
        return $this->setData(self::TYPE, trim($type));
    }

    public function setFirstName($firstName)
    {
        return $this->setData(self::FIRST_NAME, trim($firstName));
    }

    public function setLastName($lastName)
    {
        return $this->setData(self::LAST_NAME, trim($lastName));
    }

    public function setCompany($company)
    {
        return $this->setData(self::COMPANY, trim($company));
    }

    public function setStreet($street)
    {
        return $this->setData(self::STREET, trim($street));
    }

    public function setPostalCode($postalCode)
    {
        return $this->setData(self::POSTAL_CODE, trim($postalCode));
    }

    public function setCity($city)
    {
        return $this->setData(self::CITY, trim($city));
    }

    public function setCountryCode($countryCode)
    {
        return $this->setData(self::COUNTRY_CODE, trim($countryCode));
    }

    public function setPhone($phone)
    {
        return $this->setData(self::PHONE, trim($phone));
    }

    public function setMobilePhone($phone)
    {
        return $this->setData(self::MOBILE_PHONE, trim($phone));
    }

    public function setEmail($email)
    {
        return $this->setData(self::EMAIL, trim($email));
    }

    public function setMiscData($miscData)
    {
        return $this->setData(self::MISC_DATA, trim($miscData));
    }
}
