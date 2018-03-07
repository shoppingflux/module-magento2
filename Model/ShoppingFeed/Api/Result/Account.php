<?php

namespace ShoppingFeed\Manager\Model\ShoppingFeed\Api\Result;

use Magento\Framework\DataObject;
use ShoppingFeed\Manager\Model\ShoppingFeed\Api\Result\Account\Store as AccountStore;


/**
 * @method int getId()
 * @method string getApiToken()
 * @method string getLogin()
 * @method string getEmail()
 * @method string getLanguage()
 * @method AccountStore[] getStores()
 */
class Account extends DataObject
{
}
