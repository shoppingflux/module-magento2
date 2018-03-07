<?php

namespace ShoppingFeed\Manager\Model\ShoppingFeed\Api;

use Jsor\HalClient\Exception\BadResponseException;
use Jsor\HalClient\HalClient;
use Jsor\HalClient\HalResource;
use ShoppingFeed\Manager\Model\ShoppingFeed\Api\Exception\BadRequest;
use ShoppingFeed\Manager\Model\ShoppingFeed\Api\Exception\ForbiddenRequest;
use ShoppingFeed\Manager\Model\ShoppingFeed\Api\Exception\UnauthorizedRequest;
use ShoppingFeed\Manager\Model\ShoppingFeed\Api\Result\Account;
use ShoppingFeed\Manager\Model\ShoppingFeed\Api\Result\AccountFactory;
use ShoppingFeed\Manager\Model\ShoppingFeed\Api\Result\Account\StoreFactory as AccountStoreFactory;


class Client
{
    const ACCOUNT_CREATION_TOKEN = '18eaf020f7a33c08c63591c52df6a8dd3bd30d99';
    const API_URL = 'https://api.shopping-feed.com/v1/';

    /**
     * @var AccountFactory
     */
    private $accountFactory;

    /**
     * @var AccountStoreFactory
     */
    private $accountStoreFactory;

    /**
     * @param AccountFactory $accountFactory
     * @param AccountStoreFactory $accountStoreFactory
     */
    public function __construct(AccountFactory $accountFactory, AccountStoreFactory $accountStoreFactory)
    {
        $this->accountFactory = $accountFactory;
        $this->accountStoreFactory = $accountStoreFactory;
    }

    /**
     * @param string|null $apiToken
     * @return HalClient
     */
    private function getHalClient($apiToken = null)
    {
        $client = new HalClient(static::API_URL);

        if (null !== $apiToken) {
            $client = $client->withHeader('Authorization', 'Bearer ' . $apiToken);
        }

        return $client;
    }

    /**
     * @param \Closure $request
     * @return mixed
     * @throws AbstractException|\Exception
     */
    protected function catchApiErrors(\Closure $request)
    {
        try {
            $result = $request->call($this);
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
            $error = json_decode((string) $response->getBody(), true);
            $errorCode = $response->getStatusCode();
            $errorReason = '';

            if (is_array($error)) {
                if (!empty($error['status'])) {
                    $errorCode = $error['status'];
                }

                if (!empty($error['detail'])) {
                    $errorReason = $error['detail'];
                }
            }


            switch ($errorCode) {
                case 400:
                    throw new BadRequest($errorReason);
                case 401:
                    throw new UnauthorizedRequest($errorReason);
                case 403:
                    throw new ForbiddenRequest($errorReason);
            }

            throw $e;
        }

        return $result;
    }

    /**
     * @param HalClient $client
     * @param string $uri
     * @param array $options
     * @return HalResource
     * @throws AbstractException|\Exception
     */
    protected function sendGetRequest(HalClient $client, $uri, array $options = [])
    {
        return $this->catchApiErrors(
            function () use ($client, $uri, $options) {
                return $client->get($uri, $options);
            }
        );
    }

    /**
     * @param HalClient $client
     * @param string $uri
     * @param array $options
     * @return HalResource
     * @throws AbstractException|\Exception
     */
    protected function sendPostRequest(HalClient $client, $uri, array $options = [])
    {
        return $this->catchApiErrors(
            function () use ($client, $uri, $options) {
                return $client->post($uri, $options);
            }
        );
    }

    /**
     * @param string $username
     * @param string $password
     * @return string
     * @throws AbstractException|\Exception
     */
    public function getApiToken($username, $password)
    {
        $resource = $this->sendPostRequest(
            $this->getHalClient(),
            'auth',
            [
                'body' => [
                    'grant_type' => 'password',
                    'username' => $username,
                    'password' => $password,
                ],
            ]
        );

        return trim($resource->getProperty('access_token'));
    }

    public function registerAccount()
    {

    }

    /**
     * @param string $apiToken
     * @return Account
     * @throws AbstractException|\Exception
     */
    public function getAccountData($apiToken)
    {
        $resource = $this->sendGetRequest($this->getHalClient($apiToken), 'me');
        $accountResource = $resource->getFirstResource('account');
        $storeResources = $resource->getResource('store');
        $accountStores = [];

        foreach ($storeResources as $storeResource) {
            $storeFeed = (array) $storeResource->getProperty('feed');

            $accountStores[] = $this->accountStoreFactory->create(
                [
                    'id' => (int) $storeResource->getProperty('id'),
                    'name' => trim($storeResource->getProperty('name')),
                    'country' => trim($storeResource->getProperty('country')),
                    'feed_url' => $storeFeed['url'] ?? '',
                    'feed_source' => $storeFeed['source'] ?? '',
                ]
            );
        }

        $account = $this->accountFactory->create(
            [
                'id' => (int) $accountResource->getProperty('id'),
                'api_token' => trim($resource->getProperty('token')),
                'login' => trim($resource->getProperty('login')),
                'email' => trim($resource->getProperty('email')),
                'language' => trim($resource->getProperty('language')),
                'stores' => $accountStores,
            ]
        );

        return $account;
    }
}
