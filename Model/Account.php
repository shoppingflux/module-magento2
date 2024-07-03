<?php

namespace ShoppingFeed\Manager\Model;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\AbstractDb as AbstractCollection;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use ShoppingFeed\Manager\Api\Data\AccountInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Account as AccountResource;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Collection as AccountCollection;

/**
 * @method AccountResource getResource()
 * @method AccountCollection getCollection()
 */
class Account extends AbstractModel implements AccountInterface
{
    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    protected $_eventPrefix = 'shoppingfeed_manager_account';

    protected $_eventObject = 'account';

    public function __construct(
        Context $context,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractCollection $resourceCollection = null,
        EncryptorInterface $encryptor = null,
        array $data = []
    ) {
        $this->encryptor = $encryptor ?? ObjectManager::getInstance()->get(EncryptorInterface::class);

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

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
        $token = trim((string) $this->getData(self::API_TOKEN));

        if (strpos($token, ':') !== false) {
            try {
                return $this->encryptor->decrypt($token) ?: $token;
            } catch (\Exception $e) {
                // The token is likely not encrypted.
            }
        }

        return $token;
    }

    public function getShoppingFeedAccountId()
    {
        $accountId = (int) $this->getData(self::SHOPPING_FEED_ACCOUNT_ID);

        return ($accountId > 0) ? $accountId : null;
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
        return $this->setData(self::API_TOKEN, $this->encryptor->encrypt($apiToken));
    }

    public function setShoppingFeedAccountId($shoppingFeedAccountId)
    {
        return $this->setData(self::SHOPPING_FEED_ACCOUNT_ID, $shoppingFeedAccountId);
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
