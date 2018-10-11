<?php

namespace ShoppingFeed\Manager\Api\Data;

interface AccountInterface
{
    /**#@+*/
    const ACCOUNT_ID = 'account_id';
    const API_TOKEN = 'api_token';
    const SHOPPING_FEED_LOGIN = 'shopping_feed_login';
    const SHOPPING_FEED_EMAIL = 'shopping_feed_email';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    /**#@-*/

    /**
     * @return int|null
     */
    public function getId();

    /**
     * @return string
     */
    public function getApiToken();

    /**
     * @return string
     */
    public function getShoppingFeedLogin();

    /**
     * @return string
     */
    public function getShoppingFeedEmail();

    /**
     * @return string
     */
    public function getCreatedAt();

    /**
     * @return string
     */
    public function getUpdatedAt();

    /**
     * @param int $id
     * @return AccountInterface
     */
    public function setId($id);

    /**
     * @param string $apiToken
     * @return AccountInterface
     */
    public function setApiToken($apiToken);

    /**
     * @param string $shoppingFeedLogin
     * @return AccountInterface
     */
    public function setShoppingFeedLogin($shoppingFeedLogin);

    /**
     * @param string $shoppingFeedEmail
     * @return AccountInterface
     */
    public function setShoppingFeedEmail($shoppingFeedEmail);

    /**
     * @param string $createdAt
     * @return AccountInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * @param string $updatedAt
     * @return AccountInterface
     */
    public function setUpdatedAt($updatedAt);
}
