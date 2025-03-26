# Changelog

## [1.11.0] - 2025-03-26
### Added
- Implement compatibility with remote media storage
- Add `child_export_state` filter to CLI feed commands
- Allow customization of batch size for feed data updates
- Add an option to (not) disable tax for business orders
- Show `customized_url` marketplace item field on order view

### Changed
- Optimize redundant filters when refreshing feed data

## [1.10.0] - 2025-01-29
### Added
- Add option to disable country validation when importing orders
- Import item marketplace fields to order item options

## [1.9.1] - 2024-12-05
### Fixed
- Fix potential issue with feed export in multi-account configurations

## [1.9.0] - 2024-11-13
### Changed
- Allow customizing the mapping of Spain regions using DI
- Allow 3rd party plugins to choose which store view to import orders into

## [1.8.2] - 2024-07-15
### Changed
- Improve multi-store notification on the account import form

### Fixed
- Improve handling of marketplace order discounts

## [1.8.1] - 2024-07-14
### Fixed
- Fix validation of sales rules on older Magento versions

## [1.8.0] - 2024-07-03
### Added
- Implement new command to export feeds without refresh using cron tasks
- Implement support for marketplace cart discounts

### Changed
- Rework account and store management
- Optimize refreshable feed data prioritization

## [1.7.2] - 2024-05-13
### Added
- Allow exporting the `url_key` attribute in the feeds

### Changed
- Change the format of the `platform` metadata in the feeds

## [1.7.1] - 2024-04-22
### Fixed
- Prevent rare problems with options-based columns in the feed product listing
- Fix action drop-down overflow in listings
- Fill the "Company" field with a default value if required but missing
- Prevent old customer addresses from being validated

## [1.7.0] - 2023-12-19
### Added
- Import relay point IDs from the new API field

### Fixed
- Fix result of CLI commands with Magento **2.4.6** 

## [1.6.4] - 2023-12-06
### Fixed
- Fix saving of empty forced category from the product page

## [1.6.3] - 2023-11-28
### Fixed
- Apply country code mapping only when necessary

## [1.6.2] - 2023-10-19
### Fixed
- Fix mass-update of Shopping Feed product attributes

## [1.6.1] - 2023-10-05
### Fixed
- Map `UK` country code to `GB` when importing order addresses

## [1.6.0] - 2023-08-03
### Added
- Add an option to export the URLs of variations
- Add a description for the "Export Attribute Set Name" configuration field

### Changed
- Improve synchronisation of order states with Shoppingfeed
- Improve French translations

### Fixed
- Fix export of eco-tax amounts in some cases
- Work around the EQP restriction regarding `Zend_Filter_Input`

## [1.5.2] - 2023-06-28
### Fixed
- Fix compatibility with Magento **2.4.6**
- Do not attempt to render invalid option values

## [0.54.2] - 2023-05-17
### Fixed
- Fix missing default parameter values in weee tax plugin
- Fix detection of attributes usable to create configurable products
- Fix "Categorization Status" column when a text attribute is exported
- Add missing "Disabled Product" option to the "Exclusion Reason" column of the feed product listing

## [1.5.1] - 2023-03-30
### Fixed
- Fix DI compilation

## [1.5.0] - 2023-03-30
### Added
- Add ability to force a feed refresh from the account configuration

### Fixed
- Fix sending of order emails
- Hide new order configuration field when not relevant

## [1.4.0] - 2023-02-22
### Added
- Add an option to export category names or breadcrumbs
- Add an option to skip the data refresh when generating feeds from the CLI
- Differentiate between test and live orders
- Add an option to set imported customer addresses as default addresses
- Display the latest shipping date on the sales order view

### Fixed
- Fix compatibility with the `Wyomind_EstimatedDeliveryDate` module

## [1.3.2] - 2023-01-20
### Fixed
- Fix compatibility issue with PHP 8 (export of multiselect values)

## [1.3.1] - 2022-12-20
### Fixed
- Fix missing default parameter values in weee tax plugin
- Fix detection of attributes usable to create configurable products

## [1.3.0] - 2022-11-29
### Added
- Handle new `fulfilledBy` field on marketplace orders
- Add marketplace order information to the invoice view
- Add the tax identification number to the marketplace order information

### Changed
- Improve the metadata of generated feeds

### Fixed
- Fix compatibility with PHP versions < **7.3**

## [0.54.1] - 2022-10-14
### Fixed
- Fix compatibility with PHP **7.1**

## [1.2.3] - 2022-10-10
### Fixed
- Fix compatibility issue with PHP **8.1** (unnamed fieldset in rule form) (thanks to [@benjamin-volle](https://github.com/benjamin-volle))

## [1.2.2] - 2022-10-06
### Fixed
- Fix generation of notification IDs
- Fix compatibility with modules that may change the current store between two order imports
- Prevent rare problems with options-based columns in the feed product listing
- Fix filtering on the `created_at` field of the order listing on Magento **2.4.5**
- Fix compatibility issue with PHP **8.1** (unnamed block in rule form)

## [1.2.1] - 2022-09-09
### Fixed
- Fix compatibility issue with PHP **8.1** when using the price permissions module (thanks to [@benjamin-volle](https://github.com/benjamin-volle))

## [1.2.0] - 2022-08-10
### Added
- Add a "View Logs" action to the marketplace order listing

### Fixed
- Update DB schema whitelist
- Prevent new order emails from being sent in all contexts

## [1.1.1] - 2022-08-02
### Fixed
- Improve upgrade process for account configuration data
- Fix nested condition combinations in shipping method rules
- Refresh unread logs notification after importing marketplace orders

## [1.1.0] - 2022-08-02
### Added
- Allow choosing the "sku" attribute in relevant attribute-based options
- Add missing "Disabled Product" option to the "Exclusion Reason" column of the feed product listing
- Automatically detect and import regions for Spain addresses
- Add an option to import VAT IDs in billing addresses when available
- Add marketplace-related conditions to the nested conditions of shipping method rules
- Add a "read" status to marketplace order logs
- Add notifications for unread order logs and unimported orders
- Add an "Accounts" column to the cron task listing

### Changed
- Improve CLI commands (clean up output and disambiguate parameters)

### Fixed
- Fix export behavior for the shipping section
- Fix the "Categorization Status" column of the feed product listing when the values of a text attribute are exported
- Fix call to `ctype_digit` on a possibly `null` value

## [1.0.1] - 2022-04-28
### Fixed
- Fix compatibility issues with PHP **8.1**

## [1.0.0] - 2022-04-27
### Changed
- Switch from setup scripts to declarative schema

## [0.54.0] - 2022-04-27
### Added
- Implement first-class `vat`, `ecotax` and `weight` feed attributes

### Changed
- Bump dependency ranges
- Improve label for "Import Customers" option
- Disable sorting on the "Is Variation" column in the feed product listing

### Fixed
- Fix compatibility issues with Magento **2.4.4**
- Fix detection of generated feed in some cases
- Fix handling of errors in some rare cases when creating a SF account

## [0.53.2] - 2022-02-16
### Fixed
- Fix export of "stock" quantity when the "salable" quantity has already been used to detect a product state
- Fix misleading success message when a manual order import fails
- Fix misleading error message after running a cron task in some rare cases

## [0.53.1] - 2021-12-23
### Fixed
- Fix compatibility with Magento Performance Toolkit

## [0.53.0] - 2021-12-21
### Added
- Add categorization status columns to the feed product listing
- Add a suffix to the codes of additional attributes that are reserved
- Add "Save and Continue Edit" button to the account form

### Changed
- Disambiguate comment for "Export Discount Prices in" config field
- Update French translations 

### Fixed
- Improve detection of quantity changes when using MSI
- Fix import of customers with invalid characters in their name on Magento **2.4.3**
- Fix account import error message

## [0.52.0] - 2021-11-23
### Added
- Add an option to export an attribute value as the product category
- Allow to export the `created_at` and `updated_at` product attributes

### Changed
- Export by default the first category in the tree in case of a tie
- Allow default shipping fees to be set to zero

### Fixed
- Fix detection of out-of-stock products when filtering
- Improve export of options-based attributes in multi-store contexts
- Account for marketplace orders that can be split over multiple accounts
- Fix bundle adjustments not reset between two consecutive order imports

## [0.51.0] - 2021-09-09
### Added
- Add a "Fulfilled by marketplace" note on the order view page when relevant

### Fixed
- Do not decrement stock when importing fulfilled orders

## [0.50.0] - 2021-08-31
### Added
- Add options to send order and/or invoice emails for marketplace orders

### Fixed
- Fix detection of disabled products due to an invalid status value

## [0.49.0] - 2021-08-11
### Added
- Add an option to (not) export disabled products

## [0.48.0] - 2021-06-16
### Added
- Add mass-actions to the marketplace order listing

### Fixed
- Fix import of orders with bundle products in some specific cases
- Fix signature of `checkQuoteItemQty` plugin (thanks to [Viper9x](https://github.com/Viper9x))

## [0.47.0] - 2021-06-09
### Added
- Add an option to disable the automatic import of marketplace orders
- Add `billing_email` and `shipping_email` variables to default email address templates
- Add a listing for order synchronization tickets
- Add ability to choose which cron group to associate each cron task with
- Add a better error message for registration using an unsupported country

### Changed
- Use proxies for objects injected to order-related CLI commands

### Fixed
- Fix shipping of fulfilled orders when MSI stock has multiple sources

## [0.46.2] - 2021-04-29
### Fixed
- Fix calculation of shipment delays on Magento **2.3.4** to **2.3.6**
- Fix detection of changes in configuration values on some edge cases
- Fix French translation

## [0.46.1] - 2021-03-31
### Fixed
- Fix syncing of marketplace orders in some (rare) edge cases

## [0.46.0] - 2021-03-26
### Added
- Add an option to limit the length of street lines in addresses
- Add an option to specify the maximum time to wait before syncing a shipment

### Changed
- Improve detection of shipment tracking data

## [0.45.0] - 2021-03-19
### Added
- Indicate if a feed has not been generated yet in the account listing

## [0.44.0] - 2021-03-18
### Added
- Add an option to include sub-categories in the category selection
- Add a missing French translation

### Changed
- Improve the warning message when an excluded category is selected
- Synchronize more data when fetching existing marketplace orders

### Fixed
- Ensure guest mode is disabled when importing order customers
- Fix compatibility with `Mageplaza_SameOrderNumber` module

## [0.43.4] - 2021-03-02
### Fixed
- Fix compatibility with `Mageplaza_CustomOrderNumber` module

## [0.43.3] - 2021-02-18
### Fixed
- Fix multi-select config fields when a saved value becomes missing

## [0.43.2] - 2021-01-12
### Fixed
- Fix detection of ManoMano fulfilled orders

## [0.43.1] - 2021-01-05
### Fixed
- Fix compatibility with staging modules from Magento Commerce

## [0.43.0] - 2020-12-09
### Added
- Add an "Is Variation" column to the feed product listing
- Add an option to choose how to break tied category selections

## [0.42.2] - 2020-11-24
### Fixed
- Fix order import when an account has not been saved after upgrading to version **0.42.x**

## [0.42.1] - 2020-11-19
### Changed
- Display more precise errors when creating a Shopping Feed account
- Add a validation for the Shopping Feed password in the account creation form

## [0.42.0] - 2020-11-18
### Added
- Add the module version to the product feed
- Display the reason why marketplace orders can not be imported
- Add an option to force using the default email address when importing orders
- Add an option to split last names when first names are empty in order addresses

## [0.41.1] - 2020-11-04
### Fixed
- Fix the type of the "sfm_bundle_adjustments" extension attribute

## [0.41.0] - 2020-10-23
### Added
- Add an option to fetch a marketplace order by channel and reference

### Changed
- Optimize the "shoppingfeed:feed:force-automatic-refresh" CLI command
- Catch and log errors when fetching marketplace orders
- Streamline / improve buttons in UI components

### Fixed
- Fix handling of bundle products when importing marketplace orders
- Fix base fees amount in multi-currency contexts
- Ensure that WEEE tax data are loaded when using product collections
- Do not export invalid FPT values
- Improve wordings in some places

## [0.40.0] - 2020-10-08
### Added
- Implement handling of bundle products (feed export and order import)
- Add an "is_backorderable" attribute to the feed
- Improve the feed product listing (save parameters in session, display the limit date for product retention)

### Changed
- Optimize the feed refresh and generation processes
- Apply retention filters also when exporting the feed
- Do not fetch new orders when syncing existing orders

### Fixed
- Fix compatibility with Magento **2.1.18** and **2.2.9**
- Show empty options in the filters of the feed product listing

## [0.39.4] - 2020-09-17
### Fixed
- Improve compatibility with staging modules from Magento Commerce

## [0.39.3] - 2020-09-09
### Fixed
- Fix compatibility with Magento **2.3.2**

## [0.39.2] - 2020-09-02
### Changed
- Optimize the "Force Automatic Data Refresh" task

## [0.39.1] - 2020-08-11
### Changed
- Use more proxies

## [0.39.0] - 2020-08-05
### Added
- Add options to synchronize imported orders that have been refused/canceled/refunded on the marketplaces

### Fixed
- Fix dependencies in UI components with Magento **2.4.0**
- Fix fetching of stock data for products without stock management (MSI)

## [0.38.1] - 2020-07-23
### Changed
- Disable the date filters in the account listing

## [0.38.0] - 2020-07-22
### Added
- Add an option to enable a "debug mode" for order import
- Add an option to specify the delay within which orders can be imported
- Add an option to import already shipped orders
- Add a "Shopping Feed status" column to the marketplace order listing

### Fixed
- Fix links to section details in the feed product grid on Magento **2.3.5**

## [0.37.1] - 2020-07-16
### Fixed
- Deduplicate the marketplace orders before adding the new unique index

## [0.37.0] - 2020-07-01
### Added
- Export the stock statuses under the "is_in_stock" attribute
- Add a "Is Fulfilled" condition to the shipping method rules

### Fixed
- Fix the "Payment Method" conditions in the shipping method rules

## [0.36.0] - 2020-06-25
### Added
- Implement handling of fulfilled orders
- Add more notes in the order configuration

### Changed
- Identify marketplace orders by their marketplace ID and reference
- Follow PSR-12 spec for multiline if/elseif structures

## [0.35.0] - 2020-06-15
### Added
- Allow specifying dynamic default email addresses/payment method titles by marketplace (**BC break** for customer and sales order importers)

### Fixed
- Fix the import of customers without email addresses

## [0.34.2] - 2020-06-10
### Added
- Display the "is_business_order" field in the sales order view

## [0.34.1] - 2020-06-08
### Fixed
- Fix consecutive import of orders for the same customer
- Do not import orders using a non-base currency if the latter is unavailable
- Fix the handling of the current order's currency after the first import
- Fix the import of shipping and WEEE amounts with non-base currencies

## [0.34.0] - 2020-06-03
### Added
- Add marketplace informations to the sales order view

## [0.33.0] - 2020-05-11
### Added
- Add an option to manually import a new marketplace order
- Add an option to synchronize the items of non-imported marketplace orders

### Changed
- Bumped `shoppingfeed/php-sdk` dependency from **0.2.6** to **0.3.2**

### Fixed
- Disable sorting on the new "Importable" column

## [0.32.0] - 2020-04-08
### Added
- Add an option to cancel import for new marketplace orders
- Add an "Importable" column to the marketplace order listing
- Add missing french translations

### Fixed
- Regularly update the status of unimported marketplace orders

## [0.31.0] - 2020-03-26
### Added
- Add an option to import order customers (**BC break** for sales order importer)
- Implement dynamic rows config fields

### Changed
- Improve the handling of regions for some countries
- Replace hard-coded class names in error messages

## [0.30.1] - 2020-03-23
### Fixed
- Fix handling of some of the cart conditions in shipping method rules

## [0.30.0] - 2020-03-06
### Added
- Add an option to choose the product types to export
- Implement export for virtual products
- Add an option to choose how to export base and discount prices

### Fixed
- Fix compatibility with PHP 7.4

## [0.29.0] - 2020-02-28
### Added
- Add more notes/feedback in the account configuration form
- Add an option to check product websites when importing orders
- Add a "pattern" column to the shipping method rule listing

### Changed
- Improve the rendering of notes in the account configuration form

### Fixed
- Fix the translation of save buttons
- Fix some button/field labels in the cron task form
- Fix the basic shipping method applier with codes containing multiple underscores

## [0.28.1] - 2019-12-17
### Fixed
- Fix mass-update tab on recent M2 versions

## [0.28.0] - 2019-12-16
### Added
- Improve the feed product listing:
    - Add new attribute columns (type, status, visibility, price)
    - Add new feed columns (main and variation states, exclusion reason)
    - Add a sections details modal
- Implement mass-update for those product attributes: is selected, forced category
- Add an option for fetching different types of quantities when using MSI
- Implement utility methods for shipping method appliers

### Fixed
- Fix import of orders with disabled products (with or without availability check)

## [0.27.0] - 2019-12-04
### Added
-	Fetch product quantities using MSI (if available) (**BC break** for stock section adapter)

## [0.26.0] - 2019-10-31
### Changed
- Refactor shipping method appliers and improve defaults (**BC break** for custom shipping method appliers)

### Fixed
- Exclude the "All Groups" group from the options available in "Use Prices from Customer Group"
- Fix the "Category Selection" label on recent M2 versions

## [0.25.2] - 2019-10-07
### Added
- Add a details column to the order logs listing

### Fixed
- Fix usages of table codes instead of table names

## [0.25.1] - 2019-09-19
### Fixed
- Fix memory overflow with large catalogs when exporting an empty feed

## [0.25.0] - 2019-09-10
### Added
- Allow specifying a customer group with which to fetch product prices
- Import marketplace fees for orders

### Fixed
- Fix feed URL in account listing when using gzip
- Fix wrong table name used for configurable product attributes
- Force frontend config scope when executing CLI commands

## [0.24.0] - 2019-08-26
### Added
- Add an option for selecting exportable products using a custom attribute

### Changed
- Refactor attribute sources

## [0.23.1] - 2019-08-21
### Changed
- Improve prevention of stock checks when not in admin scope

## [0.23.0] - 2019-08-08
### Changed
- Remove final keywords from functions
- Improve generation of unique feed filenames

## [0.22.1] - 2019-08-01
### Added
- Import the company in order addresses
- Allow partial refunds on imported orders

### Changed
- Only fetch marketplace orders waiting shipment

### Fixed
- Fix ACL and menu configuration

## [0.22.0] - 2019-07-11
### Added
- Add new columns to the marketplace order listings
- Detect SKUs when using product IDs for order import

### Changed
- Improve default phone number handling
- Improve prevention of duplicate order import in some edge cases
- Improve prevention of stock checks for Magento 2.3

## [0.21.0] - 2019-07-03
### Added
- Add ability to create a new Shopping Feed account

### Changed
- Bump order import try count earlier
- Rework account/store management and UI

### Fixed
- Fix the "partially shipped" order status constant
- Fix french translation for "Use item reference [..]"
- Remove explicit proxies from constructors

## [0.20.0] - 2019-05-07
### Added
- Fetch the tax amount for marketplace order items

### Changed
- Bumped `shoppingfeed/php-sdk` dependency from **0.2.4** to **0.2.6**
- Improve the detection of untaxed (business) orders

### Fixed
- Fix rendering for options-based attributes with non-text labels

## [0.19.0] - 2019-04-24
### Fixed
- Fix compatibility problems with Magento **2.1.x**

## [0.18.0] - 2019-03-27
### Added
- Add "price_before_discount" and "shipping_delay" attributes to the feed

### Changed
- Force cross border trade when importing orders (togglable off)

## [0.17.1] - 2019-03-21
### Fixed
- Fix ambiguous filters in the orders listing
- Fix export of product variations in some edge cases

## [0.17.0] - 2019-03-11
### Fixed
- Fix the capacity of the Shopping Feed order ID field

## [0.16.0] - 2019-03-05
### Added
- Add explicit dependency to Guzzle

## [0.15.0] - 2019-02-26
### Added
- Handle WEEE attributes at feed export and orders import

## [0.14.1] - 2019-02-04
### Changed
- Improve the detection of product quantity changes

## [0.14.0] - 2019-01-30
### Added
- Add marketplace fields to the sales order listing
- Add the marketplace shipping and payment methods to the available conditions for shipping method rules
- Implement real-time updates for product quantities

### Changed
- Do not check product availability and options by default
- Refactor store configuration management
- Filter on active shipping method rules when importing orders

### Fixed
- Fix the orders listing (wrong join type)
- Fix the order import "super mode" on newer M2 versions

## [0.13.1] - 2019-01-02
### Fixed
- Fix order address import

## [0.13.0] - 2018-12-19
### Changed
- Import business orders without tax

### Fixed
- Fix updates batching when products retention is enabled
-  Fix translations

## [0.12.2] - 2018-12-18
### Added
- Add ability to export the attribute set name in the feed

### Fixed
- Fix initialization of timestamp fields in DataObjects
- Fix filtering on Magento # in marketplace orders listing

## [0.12.1] - 2018-12-14
### Removed
- Remove composer dependencies for packagist version

## [0.12.0] - 2018-12-14
### Added
- Register dependencies in module sequence and composer.json

### Changed
- Emulate the CLI area code rather than setting it
- Wrap "sensitive" types (wrt loading order) in proxies
- Fill missing required address fields with sensible and/or user defaults

### Fixed
- Fix translations

## [0.11.0] - 2018-12-11
### Added
- Add the listing of order logs to the sales menu

### Changed
- Fill the first name in marketplace addresses when unavailable
- Only try to import unshipped accepted orders
- Only fetch recent marketplace orders

## [0.10.0] - 2018-12-10
### Fixed
- Fix shipment syncing and SF ticket handling

## [0.9.2] - 2018-12-10
### Fixed
- Fix shipment syncing

## [0.9.1] - 2018-12-06
### Added
- Allow to use mobile phone number first for imported order addresses

## [0.9.0] - 2018-12-05
### Added
- Add ability to synchronize addresses with SF for fetched orders not imported yet

### Fixed
- Complete/fix french translations
- Fix indentation quirks
- Fix Magento # column in marketplace orders listing

## [0.8.0] - 2018-11-21
### Added
- Implement forced refresh for updated products only
- Implement price export for configurable products

### Changed
- Improve determination of category URLs

### Fixed
- Fix default cron task setup
- Fix account creation (only existing accounts are allowed for now)
- Fix parent products export in some rare cases

## [0.7.0] - 2018-11-06
### Added
- Force feed refresh in case of meaningful configuration change

### Changed
- Bumped `shoppingfeed/php-feed-generator` dependency from **1.0.0** to **1.0.2**
- Tweak various constants (database/UI)

### Fixed
- Fix product lists syncing after product save

## [0.6.0] - 2018-10-29
### Added
- Add a success message upon running a cron task
- Implement batched updates for feed data
- Add "Store View" column to account store listing
- Add the product URL to the attributes section data
- Add the platform information to the API client

### Changed
- Clean up code

### Fixed
- Fix french translations
- Fix feed refresh (force using the relevant store view + use batched updates)

## [0.5.0] - 2018-10-23
- Initial release
