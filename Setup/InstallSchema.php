<?php

namespace ShoppingFeed\Manager\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use ShoppingFeed\Manager\Api\Data\AccountInterface;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Api\Data\Feed\ProductInterface as FeedProductInterface;
use ShoppingFeed\Manager\Api\Data\Feed\Product\SectionInterface as FeedSectionInterface;
use ShoppingFeed\Manager\Model\Feed\Product as FeedProduct;
use ShoppingFeed\Manager\Model\ResourceModel\Table\Dictionary as TableDictionary;


class InstallSchema implements InstallSchemaInterface
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
     * @return void
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $this->createAccountTable($setup);
        $this->createAccountStoreTable($setup);
        $this->createFeedProductTable($setup);
        $this->createFeedSectionTypeTable($setup);
        $this->createFeedSectionTable($setup);
        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    private function createAccountTable(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $accountTableCode = $this->tableDictionary->getAccountTableCode();
        $accountTableName = $this->tableDictionary->getAccountTableName();

        if (!$setup->tableExists($accountTableCode)) {
            $table = $connection->newTable($accountTableName)
                ->addColumn(
                    AccountInterface::ACCOUNT_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'primary' => true,
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Account ID'
                )
                ->addColumn(
                    AccountInterface::API_TOKEN,
                    Table::TYPE_TEXT,
                    255,
                    [ 'nullable' => false ],
                    'API Token'
                )
                ->addColumn(
                    AccountInterface::SHOPPING_FEED_LOGIN,
                    Table::TYPE_TEXT,
                    255,
                    [ 'nullable' => false ],
                    'Shopping Feed Login'
                )
                ->addColumn(
                    AccountInterface::SHOPPING_FEED_EMAIL,
                    Table::TYPE_TEXT,
                    255,
                    [ 'nullable' => false ],
                    'Shopping Feed Email'
                )
                ->addColumn(
                    AccountInterface::CREATED_AT,
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'nullable' => false,
                        'default' => Table::TIMESTAMP_INIT,
                    ],
                    'Created At'
                )
                ->addIndex(
                    $setup->getIdxName(
                        $accountTableCode,
                        AccountInterface::API_TOKEN,
                        AdapterInterface::INDEX_TYPE_UNIQUE
                    ),
                    AccountInterface::API_TOKEN,
                    [ 'type' => AdapterInterface::INDEX_TYPE_UNIQUE ]
                )
                ->setComment('Shopping Feed Account');

            $connection->createTable($table);
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    private function createAccountStoreTable(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $accountTableCode = $this->tableDictionary->getAccountTableCode();
        $accountStoreTableCode = $this->tableDictionary->getAccountStoreTableCode();
        $accountStoreTableName = $this->tableDictionary->getAccountStoreTableName();

        if (!$setup->tableExists($accountStoreTableCode)) {
            $table = $connection->newTable($accountStoreTableName)
                ->addColumn(
                    StoreInterface::STORE_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'primary' => true,
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Account Store ID'
                )
                ->addColumn(
                    StoreInterface::ACCOUNT_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Account ID'
                )
                ->addColumn(
                    StoreInterface::BASE_STORE_ID,
                    Table::TYPE_SMALLINT,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Base Store ID'
                )
                ->addColumn(
                    StoreInterface::SHOPPING_FEED_STORE_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Shopping Feed Store ID'
                )
                ->addColumn(
                    StoreInterface::SHOPPING_FEED_NAME,
                    Table::TYPE_TEXT,
                    255,
                    [ 'nullable' => false ],
                    'Shopping Feed Name'
                )
                ->addColumn(
                    StoreInterface::CONFIGURATION,
                    Table::TYPE_TEXT,
                    16777216,
                    [
                        'nullable' => false,
                        'default' => '',
                    ],
                    'Configuration'
                )
                ->addColumn(
                    StoreInterface::CREATED_AT,
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'nullable' => false,
                        'default' => Table::TIMESTAMP_INIT,
                    ],
                    'Created At'
                )
                ->addColumn(
                    StoreInterface::UPDATED_AT,
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'nullable' => false,
                        'default' => Table::TIMESTAMP_INIT_UPDATE,
                    ],
                    'Updated At'
                )
                ->addIndex(
                    $setup->getIdxName($accountStoreTableCode, StoreInterface::ACCOUNT_ID),
                    StoreInterface::ACCOUNT_ID
                )
                ->addIndex(
                    $setup->getIdxName($accountStoreTableCode, StoreInterface::BASE_STORE_ID),
                    StoreInterface::BASE_STORE_ID
                )
                ->addIndex(
                    $setup->getIdxName(
                        $accountStoreTableName,
                        StoreInterface::SHOPPING_FEED_STORE_ID,
                        AdapterInterface::INDEX_TYPE_UNIQUE
                    ),
                    StoreInterface::SHOPPING_FEED_STORE_ID,
                    [ 'type' => AdapterInterface::INDEX_TYPE_UNIQUE ]
                )
                ->addForeignKey(
                    $setup->getFkName(
                        $accountStoreTableCode,
                        StoreInterface::ACCOUNT_ID,
                        $accountTableCode,
                        AccountInterface::ACCOUNT_ID
                    ),
                    StoreInterface::ACCOUNT_ID,
                    $setup->getTable($accountTableCode),
                    AccountInterface::ACCOUNT_ID,
                    Table::ACTION_CASCADE
                )
                ->addForeignKey(
                    $setup->getFkName($accountStoreTableCode, StoreInterface::BASE_STORE_ID, 'store', 'store_id'),
                    StoreInterface::BASE_STORE_ID,
                    $setup->getTable('store'),
                    'store_id',
                    Table::ACTION_CASCADE
                )
                ->setComment('Shopping Feed Account Store');

            $connection->createTable($table);
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    private function createFeedProductTable(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $accountStoreTableCode = $this->tableDictionary->getAccountStoreTableCode();
        $feedProductTableCode = $this->tableDictionary->getFeedProductTableCode();
        $feedProductTableName = $this->tableDictionary->getFeedProductTableName();
        $catalogCategoryTableCode = $this->tableDictionary->getCatalogCategoryTableCode();
        $catalogProductTableCode = $this->tableDictionary->getCatalogProductTableCode();

        if (!$setup->tableExists($feedProductTableCode)) {
            $table = $connection->newTable($feedProductTableName)
                ->addColumn(
                    FeedProductInterface::PRODUCT_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'primary' => true,
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Product ID'
                )
                ->addColumn(
                    FeedProductInterface::STORE_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'primary' => true,
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Store ID'
                )
                ->addColumn(
                    FeedProductInterface::IS_SELECTED,
                    Table::TYPE_BOOLEAN,
                    null,
                    [
                        'nullable' => false,
                        'default' => 0,
                    ],
                    'Is Selected'
                )
                ->addColumn(
                    FeedProductInterface::SELECTED_CATEGORY_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => true,
                        'unsigned' => true,
                    ],
                    'Selected Category ID'
                )
                ->addColumn(
                    FeedProductInterface::EXPORT_STATE,
                    Table::TYPE_SMALLINT,
                    null,
                    [
                        'nullable' => false,
                        'default' => FeedProduct::STATE_NOT_EXPORTED,
                    ],
                    'Export State'
                )
                ->addColumn(
                    FeedProductInterface::CHILD_EXPORT_STATE,
                    Table::TYPE_SMALLINT,
                    null,
                    [
                        'nullable' => false,
                        'default' => FeedProduct::STATE_NOT_EXPORTED,
                    ],
                    'Export State (as a Child)'
                )
                ->addColumn(
                    FeedProductInterface::EXPORT_RETENTION_STARTED_AT,
                    Table::TYPE_TIMESTAMP,
                    null,
                    [],
                    'Export Retention Started At'
                )
                ->addColumn(
                    FeedProductInterface::EXPORT_STATE_REFRESHED_AT,
                    Table::TYPE_TIMESTAMP,
                    null,
                    [],
                    'Export State Refreshed At'
                )
                ->addColumn(
                    FeedProductInterface::EXPORT_STATE_REFRESH_STATE,
                    Table::TYPE_SMALLINT,
                    null,
                    [
                        'nullable' => false,
                        'default' => FeedProduct::REFRESH_STATE_REQUIRED,
                    ],
                    'Export State Refresh State'
                )
                ->addColumn(
                    FeedProductInterface::EXPORT_STATE_REFRESH_STATE_UPDATED_AT,
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'nullable' => false,
                        'default' => Table::TIMESTAMP_INIT,
                    ],
                    'Export State Refresh State Updated At'
                )
                ->addIndex(
                    $setup->getIdxName($feedProductTableCode, FeedProductInterface::SELECTED_CATEGORY_ID),
                    FeedProductInterface::SELECTED_CATEGORY_ID
                )
                ->addIndex(
                    $setup->getIdxName($feedProductTableCode, FeedProductInterface::EXPORT_STATE),
                    FeedProductInterface::EXPORT_STATE
                )
                ->addIndex(
                    $setup->getIdxName($feedProductTableCode, FeedProductInterface::CHILD_EXPORT_STATE),
                    FeedProductInterface::CHILD_EXPORT_STATE
                )
                ->addIndex(
                    $setup->getIdxName($feedProductTableCode, FeedProductInterface::EXPORT_STATE_REFRESH_STATE),
                    FeedProductInterface::EXPORT_STATE_REFRESH_STATE
                )
                ->addForeignKey(
                    $setup->getFkName(
                        $feedProductTableCode,
                        FeedProductInterface::PRODUCT_ID,
                        $catalogProductTableCode,
                        'entity_id'
                    ),
                    FeedProductInterface::PRODUCT_ID,
                    $setup->getTable($catalogProductTableCode),
                    'entity_id',
                    Table::ACTION_CASCADE
                )
                ->addForeignKey(
                    $setup->getFkName(
                        $feedProductTableCode,
                        FeedProductInterface::STORE_ID,
                        $accountStoreTableCode,
                        StoreInterface::STORE_ID
                    ),
                    FeedProductInterface::STORE_ID,
                    $setup->getTable($accountStoreTableCode),
                    StoreInterface::STORE_ID,
                    Table::ACTION_CASCADE
                )
                ->addForeignKey(
                    $setup->getFkName(
                        $feedProductTableCode,
                        FeedProductInterface::SELECTED_CATEGORY_ID,
                        $catalogCategoryTableCode,
                        'entity_id'
                    ),
                    FeedProductInterface::SELECTED_CATEGORY_ID,
                    $setup->getTable($catalogCategoryTableCode),
                    'entity_id',
                    Table::ACTION_SET_NULL
                )
                ->setComment('Shopping Feed Feed Product');

            $connection->createTable($table);
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    private function createFeedSectionTypeTable(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $feedSectionTypeTableCode = $this->tableDictionary->getFeedProductSectionTypeTableCode();
        $feedSectionTypeTableName = $this->tableDictionary->getFeedProductSectionTypeTableName();

        if (!$setup->tableExists($feedSectionTypeTableCode)) {
            $table = $connection->newTable($feedSectionTypeTableName)
                ->addColumn(
                    'type_id',
                    Table::TYPE_SMALLINT,
                    null,
                    [
                        'identity' => true,
                        'primary' => true,
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Type ID'
                )
                ->addColumn(
                    'code',
                    Table::TYPE_TEXT,
                    32,
                    [ 'nullable' => false ],
                    'Code'
                )
                ->addIndex(
                    $setup->getIdxName($feedSectionTypeTableCode, 'code', AdapterInterface::INDEX_TYPE_UNIQUE),
                    'code',
                    [ 'type' => AdapterInterface::INDEX_TYPE_UNIQUE ]
                )
                ->setComment('Shopping Feed Feed Product Section Type');

            $connection->createTable($table);
        }
    }

    /**
     * @param SchemaSetupInterface $setup
     * @throws \Zend_Db_Exception
     */
    private function createFeedSectionTable(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $accountStoreTableCode = $this->tableDictionary->getAccountStoreTableCode();
        $feedProductTableCode = $this->tableDictionary->getFeedProductTableCode();
        $feedSectionTypeTableCode = $this->tableDictionary->getFeedProductSectionTypeTableCode();
        $feedSectionTableCode = $this->tableDictionary->getFeedProductSectionTableCode();
        $feedSectionTableName = $this->tableDictionary->getFeedProductSectionTableName();

        if (!$setup->tableExists($feedSectionTableCode)) {
            $table = $connection->newTable($feedSectionTableName)
                ->addColumn(
                    FeedSectionInterface::TYPE_ID,
                    Table::TYPE_SMALLINT,
                    null,
                    [
                        'primary' => true,
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Type ID'
                )
                ->addColumn(
                    FeedSectionInterface::PRODUCT_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'primary' => true,
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Product ID'
                )
                ->addColumn(
                    FeedSectionInterface::STORE_ID,
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'primary' => true,
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Store ID'
                )
                ->addColumn(
                    FeedSectionInterface::FEED_DATA,
                    Table::TYPE_TEXT,
                    65536,
                    [
                        'nullable' => false,
                        'default' => '',
                    ],
                    'Data'
                )
                ->addColumn(
                    FeedSectionInterface::REFRESHED_AT,
                    Table::TYPE_TIMESTAMP,
                    null,
                    [],
                    'Refreshed At'
                )
                ->addColumn(
                    FeedSectionInterface::REFRESH_STATE,
                    Table::TYPE_SMALLINT,
                    null,
                    [
                        'nullable' => false,
                        'default' => FeedProduct::REFRESH_STATE_REQUIRED,
                    ],
                    'Refresh State'
                )
                ->addColumn(
                    FeedSectionInterface::REFRESH_STATE_UPDATED_AT,
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'nullable' => false,
                        'default' => Table::TIMESTAMP_INIT,
                    ],
                    'Refresh State Updated At'
                )
                ->addIndex(
                    $setup->getIdxName($feedSectionTableCode, FeedSectionInterface::REFRESH_STATE),
                    FeedSectionInterface::REFRESH_STATE
                )
                ->addForeignKey(
                    $setup->getFkName(
                        $feedSectionTableCode,
                        FeedSectionInterface::TYPE_ID,
                        $feedSectionTypeTableCode,
                        'type_id'
                    ),
                    FeedSectionInterface::TYPE_ID,
                    $setup->getTable($feedSectionTypeTableCode),
                    'type_id',
                    Table::ACTION_CASCADE
                )
                ->addForeignKey(
                    $setup->getFkName(
                        $feedSectionTableCode,
                        FeedSectionInterface::PRODUCT_ID,
                        $feedProductTableCode,
                        FeedProductInterface::PRODUCT_ID
                    ),
                    FeedSectionInterface::PRODUCT_ID,
                    $setup->getTable($feedProductTableCode),
                    FeedProductInterface::PRODUCT_ID,
                    Table::ACTION_CASCADE
                )
                ->addForeignKey(
                    $setup->getFkName(
                        $feedSectionTableCode,
                        FeedSectionInterface::STORE_ID,
                        $accountStoreTableCode,
                        StoreInterface::STORE_ID
                    ),
                    FeedSectionInterface::STORE_ID,
                    $setup->getTable($accountStoreTableCode),
                    StoreInterface::STORE_ID,
                    Table::ACTION_CASCADE
                )
                ->setComment('Shopping Feed Feed Product Section');

            $connection->createTable($table);
        }
    }
}
