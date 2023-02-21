<?php

namespace ShoppingFeed\Manager\Model\Sales\Order;

use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Account\Store\ConfigInterface as BaseConfig;

interface ConfigInterface extends BaseConfig
{
    const CUSTOMER_DEFAULT_ADDRESS_IMPORT_MODE_NEVER = 'never';
    const CUSTOMER_DEFAULT_ADDRESS_IMPORT_MODE_ALWAYS = 'always';
    const CUSTOMER_DEFAULT_ADDRESS_IMPORT_MODE_IF_NONE = 'if_none';

    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldImportOrders(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return int
     */
    public function getOrderImportDelay(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return \DateTime
     */
    public function getOrderImportFromDate(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldUseItemReferenceAsProductId(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldCheckProductAvailabilityAndOptions(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldCheckProductWebsites(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldSyncNonImportedItems(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldSyncNonImportedAddresses(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldImportCustomers(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return int|null
     */
    public function getDefaultCustomerGroup(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @param string $marketplace
     * @return int|null
     */
    public function getMarketplaceCustomerGroup(StoreInterface $store, $marketplace);

    /**
     * @param StoreInterface $store
     * @return string
     */
    public function getCustomerDefaultAddressImportMode(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return string
     */
    public function getDefaultEmailAddress(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @param string $marketplace
     * @return string
     */
    public function getMarketplaceDefaultEmailAddress(StoreInterface $store, $marketplace);

    /**
     * @param StoreInterface $store
     * @param string $marketplace
     * @return mixed
     */
    public function shouldForceDefaultEmailAddressForMarketplace(StoreInterface $store, $marketplace);

    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldSplitLastNameWhenEmptyFirstName(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return string
     */
    public function getDefaultPhoneNumber(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return string
     */
    public function getAddressFieldPlaceholder(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return int|null
     */
    public function getAddressMaximumStreetLineLength(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldUseMobilePhoneNumberFirst(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldImportVatId(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return string|null
     */
    public function getDefaultPaymentMethodTitle(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @param string $marketplace
     * @return string|null
     */
    public function getMarketplacePaymentMethodTitle(StoreInterface $store, $marketplace);

    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldForceCrossBorderTrade(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldCreateInvoice(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldImportFulfilledOrders(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldCreateFulfilmentShipment(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldImportShippedOrders(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function shouldCreateShippedShipment(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @param string $marketplace
     * @return bool
     */
    public function shouldSendOrderEmailForMarketplace(StoreInterface $store, $marketplace);

    /**
     * @param StoreInterface $store
     * @param string $marketplace
     * @return bool
     */
    public function shouldSendInvoiceEmailForMarketplace(StoreInterface $store, $marketplace);

    /**
     * @param StoreInterface $store
     * @return int
     */
    public function getOrderSyncingDelay(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return \DateTime
     */
    public function getOrderSyncingFromDate(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return string
     */
    public function getOrderRefusalSyncingAction(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return string
     */
    public function getOrderCancellationSyncingAction(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return string
     */
    public function getOrderRefundSyncingAction(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return int|null
     */
    public function getShipmentSyncingMaximumDelay(StoreInterface $store);

    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function isDebugModeEnabled(StoreInterface $store);
}
