<?php

namespace ShoppingFeed\Manager\Model;

use Magento\Framework\Model\AbstractModel;
use ShoppingFeed\Manager\Api\Data\AccountInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Account as AccountResource;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Collection as AccountCollection;


/**
 * @method AccountResource getResource()
 * @method AccountCollection getCollection()
 */
class Account extends AbstractModel implements AccountInterface
{
    protected $_eventPrefix = 'shoppingfeed_manager_account';
    protected $_eventObject = 'account';

    protected function _construct()
    {
        $this->_init(AccountResource::class);
    }

    public function getId()
    {
        $id = parent::getId();
        return empty($id) ? null : (int) $id;
    }

    public function getApiToken()
    {
        return $this->getData(self::API_TOKEN);
    }

    public function getShoppingFeedLogin()
    {
        return $this->getData(self::SHOPPING_FEED_LOGIN);
    }

    public function getShoppingFeedEmail()
    {
        return $this->getData(self::SHOPPING_FEED_EMAIL);
    }

    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    public function setApiToken($apiToken)
    {
        return $this->setData(self::API_TOKEN, $apiToken);
    }

    public function setShoppingFeedLogin($shoppingFeedLogin)
    {
        return $this->setData(self::SHOPPING_FEED_LOGIN, $shoppingFeedLogin);
    }

    public function setShoppingFeedEmail($shoppingFeedEmail)
    {
        return $this->setData(self::SHOPPING_FEED_EMAIL, $shoppingFeedEmail);
    }

    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
