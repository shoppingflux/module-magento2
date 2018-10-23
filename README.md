# Shopping Feed integration module for Magento 2

## Installation

The extension must be installed via `composer`. To proceed, run these commands in your terminal:

```
composer require shoppingfeed/magento2-manager
php bin/magento module:enable ShoppingFeed_Manager
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
```
