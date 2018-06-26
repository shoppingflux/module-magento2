<?php

namespace ShoppingFeed\Manager\Model\ShoppingFeed\Api;

use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Api\Data\AccountInterface;
use ShoppingFeed\Sdk\Api\Session\SessionResource as ApiSession;
use ShoppingFeed\Sdk\Api\Store\StoreResource as ApiStore;
use ShoppingFeed\Sdk\Client\Client as ApiClient;
use ShoppingFeed\Sdk\Credential\Password as ApiPasswordCredential;
use ShoppingFeed\Sdk\Credential\Token as ApiTokenCredential;


class SessionManager
{
    /**
     * @var ApiSession[]
     */
    private $tokenSessions = [];

    /**
     * @param string $login
     * @param string $password
     * @return ApiSession
     * @throws LocalizedException
     */
    public function getSessionByLogin($login, $password)
    {
        $passwordCredential = new ApiPasswordCredential($login, $password);

        try {
            return ApiClient::createSession($passwordCredential);
        } catch (\Exception $e) {
            throw new LocalizedException(
                __('Could not connect to the API with the login "%1" and given password.', $login)
            );
        }
    }

    /**
     * @param string $token
     * @return ApiSession
     * @throws LocalizedException
     */
    public function getSessionByToken($token)
    {
        if (isset($this->tokenSessions[$token])) {
            return $this->tokenSessions[$token];
        }

        $tokenCredential = new ApiTokenCredential($token);

        try {
            $this->tokenSessions[$token] = ApiClient::createSession($tokenCredential);
        } catch (\Exception $e) {
            throw new LocalizedException(__('Could not connect to the API with the token "%1".', $token));
        }

        return $this->tokenSessions[$token];
    }

    /**
     * @param AccountInterface $account
     * @return ApiSession
     * @throws LocalizedException
     */
    public function getAccountSession(AccountInterface $account)
    {
        return $this->getSessionByToken($account->getApiToken());
    }

    /**
     * @param StoreInterface $store
     * @return ApiStore
     * @throws LocalizedException
     */
    public function getStoreApiResource(StoreInterface $store)
    {
        $shoppingFeedId = $store->getShoppingFeedStoreId();
        $apiStore = $this->getAccountSession($store->getAccount())->selectStore($shoppingFeedId);

        if (null === $apiStore) {
            throw new LocalizedException(__('Could not fetch the Shopping Feed store for ID "%1".', $shoppingFeedId));
        }

        return $apiStore;
    }
}
