<?php

namespace ShoppingFeed\Manager\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Api\Data\Cron\TaskInterface as CronTaskInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\LogInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\TicketInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\AddressInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\ItemInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Table\Dictionary as TableDictionary;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @var TableDictionary
     */
    private $tableDictionary;

    /**
     * @param TableDictionary $tableDictionary
     */
    public function __construct(TableDictionary $tableDictionary)
    {
        $this->tableDictionary = $tableDictionary;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Zend_Db_Exception
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $moduleVersion = $context->getVersion();

        if (empty($moduleVersion) || (version_compare($moduleVersion, '0.3.0') < 0)) {
            $this->createMarketplaceOrderTable($setup);
            $this->createMarketplaceOrderAddressTable($setup);
            $this->createMarketplaceOrderItemTable($setup);
            $this->createMarketplaceOrderTicketTable($setup);
            $this->createMarketplaceOrderLogTable($setup);
            $this->createShippingMethodRuleTable($setup);
        }

        if (empty($moduleVersion) || (version_compare($moduleVersion, '0.4.0') < 0)) {
            $this->addMarketplaceOrderImportRemainingTryCountField($setup);
            $this->addMarketplaceOrderAddressMobilePhoneField($setup);
            $this->renameMarketplaceOrderTicketOrderIdField($setup);
            $this->renameMarketplaceOrderTicketActionField($setup);
            $this->addMarketplaceOrderTicketShoppingFeedIdUniqueIndex($setup);
            $this->addMarketplaceOrderTicketStatusField($setup);
            $this->renameMarketplaceOrderLogOrderIdField($setup);
            $this->addMarketplaceOrderLogDetailsField($setup);
        }

        if (empty($moduleVersion) || (version_compare($moduleVersion, '0.5.0') < 0)) {
            $this->createCronTaskTable($setup);
            $this->changeMarketplaceOrderTicketShoppingFeedIdFieldType($setup);
        }

        if (empty($moduleVersion) || (version_compare($moduleVersion, '0.10.0') < 0)) {
            $this->changeMarketplaceOrderTicketShoppingFeedIdFieldType($setup);
        }

        if (empty($moduleVersion) || (version_compare($moduleVersion, '0.13.0') < 0)) {
            $this->addMarketplaceOrderAdditionalFieldsField($setup);
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    private function createMarketplaceOrderTable(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $accountStoreTableCode = $this->tableDictionary->getAccountStoreTableCode();
        $marketplaceOrderTableCode = $this->tableDictionary->getMarketplaceOrderTableCode();
        $marketplaceOrderTableName = $this->tableDictionary->getMarketplaceOrderTableName();
        $salesOrderTableCode = $this->tableDictionary->getSalesOrderTableCode();

        if (!$setup->tableExists($marketplaceOrderTableCode)) {
            $table = $connection->newTable($marketplaceOrderTableName)
                ->addColumn(
                    OrderInterface::ORDER_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'primary' => true,
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Order ID'
                )
                ->addColumn(
                    OrderInterface::STORE_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => true,
                        'unsigned' => true,
                    ],
                    'Store ID'
                )
                ->addColumn(
                    OrderInterface::SALES_ORDER_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => true,
                        'unsigned' => true,
                    ],
                    'Sales Order ID'
                )
                ->addColumn(
                    OrderInterface::SHOPPING_FEED_ORDER_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Shopping Feed Order ID'
                )
                ->addColumn(
                    OrderInterface::MARKETPLACE_ORDER_NUMBER,
                    Table::TYPE_TEXT,
                    255,
                    [ 'nullable' => false ],
                    'Marketplace Order Number'
                )
                ->addColumn(
                    OrderInterface::SHOPPING_FEED_MARKETPLACE_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Shopping Feed Marketplace ID'
                )
                ->addColumn(
                    OrderInterface::MARKETPLACE_NAME,
                    Table::TYPE_TEXT,
                    255,
                    [ 'nullable' => false ],
                    'Marketplace Name'
                )
                ->addColumn(
                    OrderInterface::SHOPPING_FEED_STATUS,
                    Table::TYPE_TEXT,
                    64,
                    [ 'nullable' => false ],
                    'Shopping Feed Status'
                )
                ->addColumn(
                    OrderInterface::CURRENCY_CODE,
                    Table::TYPE_TEXT,
                    3,
                    [ 'nullable' => false ],
                    'Currency Code'
                )
                ->addColumn(
                    OrderInterface::PRODUCT_AMOUNT,
                    Table::TYPE_DECIMAL,
                    [ 12, 4 ],
                    [ 'nullable' => false ],
                    'Product Amount'
                )
                ->addColumn(
                    OrderInterface::SHIPPING_AMOUNT,
                    Table::TYPE_DECIMAL,
                    [ 12, 4 ],
                    [ 'nullable' => false ],
                    'Shipping Amount'
                )
                ->addColumn(
                    OrderInterface::TOTAL_AMOUNT,
                    Table::TYPE_DECIMAL,
                    [ 12, 4 ],
                    [ 'nullable' => false ],
                    'Total Amount'
                )
                ->addColumn(
                    OrderInterface::PAYMENT_METHOD,
                    Table::TYPE_TEXT,
                    255,
                    [ 'nullable' => false ],
                    'Payment Method'
                )
                ->addColumn(
                    OrderInterface::SHIPMENT_CARRIER,
                    Table::TYPE_TEXT,
                    255,
                    [ 'nullable' => false ],
                    'Shipment Carrier'
                )
                ->addColumn(
                    OrderInterface::CREATED_AT,
                    Table::TYPE_DATETIME,
                    null,
                    [ 'nullable' => false ],
                    'Created At'
                )
                ->addColumn(
                    OrderInterface::UPDATED_AT,
                    Table::TYPE_DATETIME,
                    null,
                    [ 'nullable' => false ],
                    'Updated At'
                )
                ->addColumn(
                    OrderInterface::FETCHED_AT,
                    Table::TYPE_DATETIME,
                    null,
                    [ 'nullable' => false ],
                    'Fetched At'
                )
                ->addColumn(
                    OrderInterface::IMPORTED_AT,
                    Table::TYPE_DATETIME,
                    null,
                    [ 'nullable' => true ],
                    'Imported At'
                )
                ->addColumn(
                    OrderInterface::ACKNOWLEDGED_AT,
                    Table::TYPE_DATETIME,
                    null,
                    [ 'nullable' => true ],
                    'Acknowledged At'
                )
                ->addIndex(
                    $setup->getIdxName(
                        $marketplaceOrderTableCode,
                        OrderInterface::SALES_ORDER_ID,
                        AdapterInterface::INDEX_TYPE_UNIQUE
                    ),
                    OrderInterface::SALES_ORDER_ID,
                    [ 'type' => AdapterInterface::INDEX_TYPE_UNIQUE ]
                )
                ->addIndex(
                    $setup->getIdxName(
                        $salesOrderTableCode,
                        OrderInterface::SHOPPING_FEED_ORDER_ID,
                        AdapterInterface::INDEX_TYPE_UNIQUE
                    ),
                    OrderInterface::SHOPPING_FEED_ORDER_ID,
                    [ 'type' => AdapterInterface::INDEX_TYPE_UNIQUE ]
                )
                ->addForeignKey(
                    $setup->getFkName(
                        $marketplaceOrderTableCode,
                        OrderInterface::STORE_ID,
                        $accountStoreTableCode,
                        StoreInterface::STORE_ID
                    ),
                    OrderInterface::STORE_ID,
                    $setup->getTable($accountStoreTableCode),
                    StoreInterface::STORE_ID,
                    Table::ACTION_SET_NULL
                )
                ->addForeignKey(
                    $setup->getFkName(
                        $marketplaceOrderTableCode,
                        OrderInterface::SALES_ORDER_ID,
                        $salesOrderTableCode,
                        'entity_id'
                    ),
                    OrderInterface::SALES_ORDER_ID,
                    $setup->getTable($salesOrderTableCode),
                    'entity_id',
                    Table::ACTION_SET_NULL
                )
                ->setComment('Shopping Feed Marketplace Order');

            $connection->createTable($table);
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function addMarketplaceOrderImportRemainingTryCountField(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $marketplaceOrderTableName = $this->tableDictionary->getMarketplaceOrderTableName();

        if (!$connection->tableColumnExists($marketplaceOrderTableName, OrderInterface::IMPORT_REMAINING_TRY_COUNT)) {
            $connection->addColumn(
                $marketplaceOrderTableName,
                OrderInterface::IMPORT_REMAINING_TRY_COUNT,
                [
                    'type' => Table::TYPE_INTEGER,
                    'nullable' => false,
                    'unsigned' => true,
                    'default' => OrderInterface::DEFAULT_IMPORT_TRY_COUNT,
                    'comment' => 'Import Remaining Try Count',
                    'after' => OrderInterface::SHIPMENT_CARRIER,
                ]
            );
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    private function createMarketplaceOrderAddressTable(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $marketplaceOrderTableCode = $this->tableDictionary->getMarketplaceOrderTableCode();
        $marketplaceOrderTableName = $this->tableDictionary->getMarketplaceOrderTableName();
        $marketplaceOrderAddressTableCode = $this->tableDictionary->getMarketplaceOrderAddressTableCode();
        $marketplaceOrderAddressTableName = $this->tableDictionary->getMarketplaceOrderAddressTableName();

        if (!$setup->tableExists($marketplaceOrderAddressTableCode)) {
            $table = $connection->newTable($marketplaceOrderAddressTableName)
                ->addColumn(
                    AddressInterface::ADDRESS_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'primary' => true,
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Address ID'
                )
                ->addColumn(
                    AddressInterface::ORDER_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Order ID'
                )
                ->addColumn(
                    AddressInterface::TYPE,
                    Table::TYPE_TEXT,
                    255,
                    [ 'nullable' => false ],
                    'Type'
                )
                ->addColumn(
                    AddressInterface::FIRST_NAME,
                    Table::TYPE_TEXT,
                    255,
                    [ 'nullable' => false ],
                    'First Name'
                )
                ->addColumn(
                    AddressInterface::LAST_NAME,
                    Table::TYPE_TEXT,
                    255,
                    [ 'nullable' => false ],
                    'Last Name'
                )
                ->addColumn(
                    AddressInterface::COMPANY,
                    Table::TYPE_TEXT,
                    255,
                    [ 'nullable' => false ],
                    'Company'
                )
                ->addColumn(
                    AddressInterface::STREET,
                    Table::TYPE_TEXT,
                    255,
                    [ 'nullable' => false ],
                    'Street'
                )
                ->addColumn(
                    AddressInterface::POSTAL_CODE,
                    Table::TYPE_TEXT,
                    255,
                    [ 'nullable' => false ],
                    'Postal Code'
                )
                ->addColumn(
                    AddressInterface::CITY,
                    Table::TYPE_TEXT,
                    255,
                    [ 'nullable' => false ],
                    'City'
                )
                ->addColumn(
                    AddressInterface::COUNTRY_CODE,
                    Table::TYPE_TEXT,
                    2,
                    [ 'nullable' => false ],
                    'Country Code'
                )
                ->addColumn(
                    AddressInterface::PHONE,
                    Table::TYPE_TEXT,
                    255,
                    [ 'nullable' => false ],
                    'Phone'
                )
                ->addColumn(
                    AddressInterface::EMAIL,
                    Table::TYPE_TEXT,
                    255,
                    [ 'nullable' => false ],
                    'Email'
                )
                ->addColumn(
                    AddressInterface::MISC_DATA,
                    Table::TYPE_TEXT,
                    255,
                    [ 'nullable' => false ],
                    'Misc Data'
                )
                ->addIndex(
                    $setup->getIdxName(
                        $marketplaceOrderAddressTableCode,
                        [ AddressInterface::ORDER_ID, AddressInterface::TYPE ],
                        AdapterInterface::INDEX_TYPE_UNIQUE
                    ),
                    [ AddressInterface::ORDER_ID, AddressInterface::TYPE ],
                    [ 'type' => AdapterInterface::INDEX_TYPE_UNIQUE ]
                )
                ->addForeignKey(
                    $setup->getFkName(
                        $marketplaceOrderAddressTableCode,
                        AddressInterface::ORDER_ID,
                        $marketplaceOrderTableCode,
                        OrderInterface::ORDER_ID
                    ),
                    AddressInterface::ORDER_ID,
                    $marketplaceOrderTableName,
                    OrderInterface::ORDER_ID,
                    Table::ACTION_CASCADE
                )
                ->setComment('Shopping Feed Marketplace Order Address');

            $connection->createTable($table);
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function addMarketplaceOrderAddressMobilePhoneField(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $marketplaceOrderAddressTableName = $this->tableDictionary->getMarketplaceOrderAddressTableName();

        if (!$connection->tableColumnExists($marketplaceOrderAddressTableName, AddressInterface::MOBILE_PHONE)) {
            $connection->addColumn(
                $marketplaceOrderAddressTableName,
                AddressInterface::MOBILE_PHONE,
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 255,
                    'nullable' => false,
                    'comment' => 'Mobile Phone',
                    'after' => AddressInterface::PHONE,
                ]
            );
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    private function createMarketplaceOrderItemTable(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $marketplaceOrderTableCode = $this->tableDictionary->getMarketplaceOrderTableCode();
        $marketplaceOrderTableName = $this->tableDictionary->getMarketplaceOrderTableName();
        $marketplaceOrderItemTableCode = $this->tableDictionary->getMarketplaceOrderItemTableCode();
        $marketplaceOrderItemTableName = $this->tableDictionary->getMarketplaceOrderItemTableName();

        if (!$setup->tableExists($marketplaceOrderItemTableCode)) {
            $table = $connection->newTable($marketplaceOrderItemTableName)
                ->addColumn(
                    ItemInterface::ITEM_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'primary' => true,
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Item ID'
                )
                ->addColumn(
                    ItemInterface::ORDER_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Order ID'
                )
                ->addColumn(
                    ItemInterface::REFERENCE,
                    Table::TYPE_TEXT,
                    255,
                    [ 'nullable' => false ],
                    'Reference'
                )
                ->addColumn(
                    ItemInterface::QUANTITY,
                    Table::TYPE_DECIMAL,
                    [ 12, 4 ],
                    [ 'nullable' => false ],
                    'Quantity'
                )
                ->addColumn(
                    ItemInterface::PRICE,
                    Table::TYPE_DECIMAL,
                    [ 12, 4 ],
                    [ 'nullable' => false ],
                    'Prices'
                )
                ->addForeignKey(
                    $setup->getFkName(
                        $marketplaceOrderItemTableCode,
                        ItemInterface::ORDER_ID,
                        $marketplaceOrderTableCode,
                        OrderInterface::ORDER_ID
                    ),
                    ItemInterface::ORDER_ID,
                    $marketplaceOrderTableName,
                    OrderInterface::ORDER_ID,
                    Table::ACTION_CASCADE
                )
                ->setComment('Shopping Feed Marketplace Order Item');

            $connection->createTable($table);
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    private function createMarketplaceOrderTicketTable(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $marketplaceOrderTableCode = $this->tableDictionary->getMarketplaceOrderTableCode();
        $marketplaceOrderTableName = $this->tableDictionary->getMarketplaceOrderTableName();
        $marketplaceOrderTicketTableCode = $this->tableDictionary->getMarketplaceOrderTicketTableCode();
        $marketplaceOrderTicketTableName = $this->tableDictionary->getMarketplaceOrderTicketTableName();

        if (!$setup->tableExists($marketplaceOrderTicketTableCode)) {
            $table = $connection->newTable($marketplaceOrderTicketTableName)
                ->addColumn(
                    TicketInterface::TICKET_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'primary' => true,
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Ticket ID'
                )
                ->addColumn(
                    TicketInterface::SHOPPING_FEED_TICKET_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Shopping Feed Ticket ID'
                )
                ->addColumn(
                    TicketInterface::ORDER_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Marketplace Order ID'
                )
                ->addColumn(
                    TicketInterface::ACTION,
                    Table::TYPE_TEXT,
                    32,
                    [ 'nullable' => false ],
                    'Action'
                )
                ->addColumn(
                    TicketInterface::CREATED_AT,
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'nullable' => false,
                        'default' => Table::TIMESTAMP_INIT,
                    ],
                    'Created At'
                )
                ->addForeignKey(
                    $setup->getFkName(
                        $marketplaceOrderTicketTableCode,
                        TicketInterface::ORDER_ID,
                        $marketplaceOrderTableCode,
                        OrderInterface::ORDER_ID
                    ),
                    TicketInterface::ORDER_ID,
                    $marketplaceOrderTableName,
                    OrderInterface::ORDER_ID,
                    Table::ACTION_CASCADE
                )
                ->setComment('Shopping Feed Marketplace Order Ticket');

            $connection->createTable($table);
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function renameMarketplaceOrderTicketOrderIdField(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $marketplaceOrderTicketTableName = $this->tableDictionary->getMarketplaceOrderTicketTableName();

        if ($connection->tableColumnExists($marketplaceOrderTicketTableName, 'marketplace_order_id')
            && !$connection->tableColumnExists($marketplaceOrderTicketTableName, TicketInterface::ORDER_ID)
        ) {
            $connection->changeColumn(
                $marketplaceOrderTicketTableName,
                'marketplace_order_id',
                TicketInterface::ORDER_ID,
                [
                    'type' => Table::TYPE_INTEGER,
                    'nullable' => false,
                    'unsigned' => true,
                    'comment' => 'Order ID',
                ]
            );
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function renameMarketplaceOrderTicketActionField(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $marketplaceOrderTicketTableName = $this->tableDictionary->getMarketplaceOrderTicketTableName();

        if ($connection->tableColumnExists($marketplaceOrderTicketTableName, 'action_code')
            && !$connection->tableColumnExists($marketplaceOrderTicketTableName, TicketInterface::ACTION)
        ) {
            $connection->changeColumn(
                $marketplaceOrderTicketTableName,
                'action_code',
                TicketInterface::ACTION,
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 32,
                    'nullable' => false,
                    'comment' => 'Action',
                ]
            );
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function addMarketplaceOrderTicketShoppingFeedIdUniqueIndex(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $marketplaceOrderTicketTableCode = $this->tableDictionary->getMarketplaceOrderTicketTableCode();
        $marketplaceOrderTicketTableName = $this->tableDictionary->getMarketplaceOrderTicketTableName();

        $indexName = $setup->getIdxName(
            $marketplaceOrderTicketTableCode,
            [ TicketInterface::SHOPPING_FEED_TICKET_ID ],
            AdapterInterface::INDEX_TYPE_UNIQUE
        );

        $tableIndices = $connection->getIndexList($marketplaceOrderTicketTableName);

        if (!isset($tableIndices[$indexName])) {
            $connection->addIndex(
                $marketplaceOrderTicketTableName,
                $indexName,
                [ TicketInterface::SHOPPING_FEED_TICKET_ID ],
                AdapterInterface::INDEX_TYPE_UNIQUE
            );
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function addMarketplaceOrderTicketStatusField(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $marketplaceOrderTicketTableName = $this->tableDictionary->getMarketplaceOrderTicketTableName();

        if (!$connection->tableColumnExists($marketplaceOrderTicketTableName, TicketInterface::STATUS)) {
            $connection->addColumn(
                $marketplaceOrderTicketTableName,
                TicketInterface::STATUS,
                [
                    'type' => Table::TYPE_INTEGER,
                    'nullable' => false,
                    'unsigned' => true,
                    'default' => TicketInterface::STATUS_PENDING,
                    'comment' => 'Status',
                    'after' => TicketInterface::ACTION,
                ]
            );
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    private function createMarketplaceOrderLogTable(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $marketplaceOrderTableCode = $this->tableDictionary->getMarketplaceOrderTableCode();
        $marketplaceOrderTableName = $this->tableDictionary->getMarketplaceOrderTableName();
        $marketplaceOrderLogTableCode = $this->tableDictionary->getMarketplaceOrderLogTableCode();
        $marketplaceOrderLogTableName = $this->tableDictionary->getMarketplaceOrderLogTableName();

        if (!$setup->tableExists($marketplaceOrderLogTableCode)) {
            $table = $connection->newTable($marketplaceOrderLogTableName)
                ->addColumn(
                    LogInterface::LOG_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'primary' => true,
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Log ID'
                )
                ->addColumn(
                    LogInterface::ORDER_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Order ID'
                )
                ->addColumn(
                    LogInterface::TYPE,
                    Table::TYPE_TEXT,
                    32,
                    [ 'nullable' => false ],
                    'Type'
                )
                ->addColumn(
                    LogInterface::MESSAGE,
                    Table::TYPE_TEXT,
                    65536,
                    [ 'nullable' => false ],
                    'Message'
                )
                ->addColumn(
                    LogInterface::CREATED_AT,
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'nullable' => false,
                        'default' => Table::TIMESTAMP_INIT,
                    ],
                    'Created At'
                )
                ->addForeignKey(
                    $setup->getFkName(
                        $marketplaceOrderLogTableCode,
                        LogInterface::ORDER_ID,
                        $marketplaceOrderTableCode,
                        OrderInterface::ORDER_ID
                    ),
                    LogInterface::ORDER_ID,
                    $marketplaceOrderTableName,
                    OrderInterface::ORDER_ID,
                    Table::ACTION_CASCADE
                )
                ->setComment('Shopping Feed Marketplace Order Log');

            $connection->createTable($table);
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function renameMarketplaceOrderLogOrderIdField(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $marketplaceOrderLogTableName = $this->tableDictionary->getMarketplaceOrderLogTableName();

        if ($connection->tableColumnExists($marketplaceOrderLogTableName, 'marketplace_order_id')
            && !$connection->tableColumnExists($marketplaceOrderLogTableName, LogInterface::ORDER_ID)
        ) {
            $connection->changeColumn(
                $marketplaceOrderLogTableName,
                'marketplace_order_id',
                LogInterface::ORDER_ID,
                [
                    'type' => Table::TYPE_INTEGER,
                    'nullable' => false,
                    'unsigned' => true,
                    'comment' => 'Order ID',
                ]
            );
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function addMarketplaceOrderLogDetailsField(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $marketplaceOrderLogTableName = $this->tableDictionary->getMarketplaceOrderLogTableName();

        if (!$connection->tableColumnExists($marketplaceOrderLogTableName, LogInterface::DETAILS)) {
            $connection->addColumn(
                $marketplaceOrderLogTableName,
                LogInterface::DETAILS,
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 65536,
                    'nullable' => true,
                    'comment' => 'Details',
                    'after' => LogInterface::MESSAGE,
                ]
            );
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    private function createShippingMethodRuleTable(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $shippingMethodRuleTableCode = $this->tableDictionary->getShippingMethodRuleTableCode();
        $shippingMethodRuleTableName = $this->tableDictionary->getShippingMethodRuleTableName();

        if (!$setup->tableExists($shippingMethodRuleTableCode)) {
            $table = $connection->newTable($shippingMethodRuleTableName)
                ->addColumn(
                    'rule_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'primary' => true,
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Rule ID'
                )
                ->addColumn(
                    'name',
                    Table::TYPE_TEXT,
                    255,
                    [ 'nullable' => false ],
                    'Name'
                )
                ->addColumn(
                    'description',
                    Table::TYPE_TEXT,
                    65536,
                    [ 'nullable' => false ],
                    'Description'
                )
                ->addColumn(
                    'from_date',
                    Table::TYPE_DATE,
                    null,
                    [ 'nullable' => true ],
                    'From Date'
                )
                ->addColumn(
                    'to_date',
                    Table::TYPE_DATE,
                    null,
                    [ 'nullable' => true ],
                    'To Date'
                )
                ->addColumn(
                    'is_active',
                    Table::TYPE_BOOLEAN,
                    null,
                    [
                        'nullable' => false,
                        'default' => 0,
                    ],
                    'Is Active'
                )
                ->addColumn(
                    'conditions_serialized',
                    Table::TYPE_TEXT,
                    16777216,
                    [ 'nullable' => true ],
                    'Serialized Conditions'
                )
                ->addColumn(
                    'applier_code',
                    Table::TYPE_TEXT,
                    255,
                    [ 'nullable' => false ],
                    'Applier Code'
                )
                ->addColumn(
                    'applier_configuration',
                    Table::TYPE_TEXT,
                    65536,
                    [ 'nullable' => true ],
                    'Applier Configuration'
                )
                ->addColumn(
                    'sort_order',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                        'default' => 0,
                    ],
                    'Sort Order'
                )
                ->addColumn(
                    'created_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'nullable' => false,
                        'default' => Table::TIMESTAMP_INIT,
                    ],
                    'Created At'
                )
                ->addColumn(
                    'updated_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'nullable' => false,
                        'default' => Table::TIMESTAMP_INIT_UPDATE,
                    ],
                    'Updated At'
                )
                ->setComment('Shopping Feed Shipping Method Rule');

            $connection->createTable($table);
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    private function createCronTaskTable(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $cronTaskTableCode = $this->tableDictionary->getCronTaskTableCode();
        $cronTaskTableName = $this->tableDictionary->getCronTaskTableName();

        if (!$setup->tableExists($cronTaskTableCode)) {
            $table = $connection->newTable($cronTaskTableName)
                ->addColumn(
                    CronTaskInterface::TASK_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'primary' => true,
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Task ID'
                )
                ->addColumn(
                    CronTaskInterface::NAME,
                    Table::TYPE_TEXT,
                    255,
                    [ 'nullable' => false ],
                    'Name'
                )
                ->addColumn(
                    CronTaskInterface::DESCRIPTION,
                    Table::TYPE_TEXT,
                    65536,
                    [ 'nullable' => false ],
                    'Description'
                )
                ->addColumn(
                    CronTaskInterface::COMMAND_CODE,
                    Table::TYPE_TEXT,
                    255,
                    [ 'nullable' => false ],
                    'Command Code'
                )
                ->addColumn(
                    CronTaskInterface::COMMAND_CONFIGURATION,
                    Table::TYPE_TEXT,
                    65536,
                    [ 'nullable' => true ],
                    'Command Configuration'
                )
                ->addColumn(
                    CronTaskInterface::SCHEDULE_TYPE,
                    Table::TYPE_TEXT,
                    255,
                    [ 'nullable' => false ],
                    'Schedule Type'
                )
                ->addColumn(
                    CronTaskInterface::CRON_EXPRESSION,
                    Table::TYPE_TEXT,
                    255,
                    [ 'nullable' => true ],
                    'Cron Expression'
                )
                ->addColumn(
                    CronTaskInterface::IS_ACTIVE,
                    Table::TYPE_BOOLEAN,
                    null,
                    [
                        'nullable' => false,
                        'default' => 1,
                    ],
                    'Is Active'
                )
                ->addColumn(
                    CronTaskInterface::CREATED_AT,
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'nullable' => false,
                        'default' => Table::TIMESTAMP_INIT,
                    ],
                    'Created At'
                )
                ->addColumn(
                    CronTaskInterface::UPDATED_AT,
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'nullable' => false,
                        'default' => Table::TIMESTAMP_INIT_UPDATE,
                    ],
                    'Updated At'
                )
                ->setComment('Shopping Feed Cron Task');

            $connection->createTable($table);
        }
    }

    public function changeMarketplaceOrderTicketShoppingFeedIdFieldType(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();

        $connection->modifyColumn(
            $this->tableDictionary->getMarketplaceOrderTicketTableName(),
            TicketInterface::SHOPPING_FEED_TICKET_ID,
            [
                'type' => Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'comment' => 'Shopping Feed Ticket ID',
            ]
        );
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    private function addMarketplaceOrderAdditionalFieldsField(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $marketplaceOrderTableName = $this->tableDictionary->getMarketplaceOrderTableName();

        if (!$connection->tableColumnExists($marketplaceOrderTableName, OrderInterface::ADDITIONAL_FIELDS)) {
            $connection->addColumn(
                $marketplaceOrderTableName,
                OrderInterface::ADDITIONAL_FIELDS,
                [
                    'type' => Table::TYPE_TEXT,
                    'length' => 65536,
                    'nullable' => true,
                    'comment' => 'Additional Fields',
                    'after' => OrderInterface::SHIPMENT_CARRIER,
                ]
            );
        }
    }
}
