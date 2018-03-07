<?php

namespace ShoppingFeed\Manager\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

use ShoppingFeed\Manager\Model\Feed\Product as FeedProduct;
use ShoppingFeed\Manager\Model\Feed\Section as FeedSection;
use ShoppingFeed\Manager\Model\Feed\Refresher as FeedRefresher;


class InstallSchema implements InstallSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $connection = $setup->getConnection();
        $setup->startSetup();

        /**
         * Create "sfm_account" table
         */
        $accountTableName = 'sfm_account';

        if (!$setup->tableExists($accountTableName)) {
            $table = $connection->newTable($setup->getTable($accountTableName))
                ->addColumn(
                    'account_id',
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
                    'shopping_feed_account_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Shopping Feed Account ID'
                )
                ->addColumn(
                    'api_token',
                    Table::TYPE_TEXT,
                    255,
                    [ 'nullable' => false ],
                    'API Token'
                )
                ->addColumn(
                    'shopping_feed_login',
                    Table::TYPE_TEXT,
                    255,
                    [ 'nullable' => false ],
                    'Shopping Feed Login'
                )
                ->addColumn(
                    'shopping_feed_email',
                    Table::TYPE_TEXT,
                    255,
                    [ 'nullable' => false ],
                    'Shopping Feed Email'
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
                ->addIndex(
                    $setup->getIdxName(
                        $accountTableName,
                        'shopping_feed_account_id',
                        AdapterInterface::INDEX_TYPE_UNIQUE
                    ),
                    'shopping_feed_account_id',
                    [ 'type' => AdapterInterface::INDEX_TYPE_UNIQUE ]
                )
                ->addIndex(
                    $setup->getIdxName($accountTableName, 'api_token', AdapterInterface::INDEX_TYPE_UNIQUE),
                    'api_token',
                    [ 'type' => AdapterInterface::INDEX_TYPE_UNIQUE ]
                )
                ->setComment('Shopping Feed Account');

            $connection->createTable($table);
        }

        /**
         * Create "sfm_account_store" table
         *
         */
        $accountStoreTableName = 'sfm_account_store';

        if (!$setup->tableExists($accountStoreTableName)) {
            $table = $connection->newTable($setup->getTable($accountStoreTableName))
                ->addColumn(
                    'store_id',
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
                    'account_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Account ID'
                )
                ->addColumn(
                    'base_store_id',
                    Table::TYPE_SMALLINT,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Base Store ID'
                )
                ->addColumn(
                    'shopping_feed_store_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => false,
                        'unsigned' => true,
                    ],
                    'Shopping Feed Store ID'
                )
                ->addColumn(
                    'shopping_feed_name',
                    Table::TYPE_TEXT,
                    255,
                    [ 'nullable' => false ],
                    'Shopping Feed Name'
                )
                ->addColumn(
                    'configuration',
                    Table::TYPE_TEXT,
                    16777215,
                    [
                        'nullable' => false,
                        'default' => '',
                    ],
                    'Configuration'
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
                ->addIndex(
                    $setup->getIdxName($accountStoreTableName, 'account_id'),
                    'account_id'
                )
                ->addIndex(
                    $setup->getIdxName($accountStoreTableName, 'base_store_id'),
                    'base_store_id'
                )
                ->addIndex(
                    $setup->getIdxName(
                        $accountStoreTableName,
                        'shopping_feed_store_id',
                        AdapterInterface::INDEX_TYPE_UNIQUE
                    ),
                    'shopping_feed_store_id',
                    [ 'type' => AdapterInterface::INDEX_TYPE_UNIQUE ]
                )
                ->addForeignKey(
                    $setup->getFkName($accountStoreTableName, 'account_id', $accountTableName, 'account_id'),
                    'account_id',
                    $setup->getTable($accountTableName),
                    'account_id',
                    Table::ACTION_CASCADE
                )
                ->addForeignKey(
                    $setup->getFkName($accountStoreTableName, 'base_store_id', 'store', 'store_id'),
                    'base_store_id',
                    $setup->getTable('store'),
                    'store_id',
                    Table::ACTION_CASCADE
                )
                ->setComment('Shopping Feed Account Store');

            $connection->createTable($table);
        }

        /**
         * Create "sfm_feed_product" table
         */
        $feedProductTableName = 'sfm_feed_product';
        $catalogProductTableName = 'catalog_product_entity';
        $catalogCategoryTableName = 'catalog_category_entity';

        if (!$setup->tableExists($feedProductTableName)) {
            $table = $connection->newTable($setup->getTable($feedProductTableName))
                ->addColumn(
                    'product_id',
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
                    'store_id',
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
                    'is_selected',
                    Table::TYPE_BOOLEAN,
                    null,
                    [
                        'nullable' => false,
                        'default' => 0,
                    ],
                    'Is Selected'
                )
                ->addColumn(
                    'selected_category_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'nullable' => true,
                        'unsigned' => true,
                    ],
                    'Selected Category ID'
                )
                ->addColumn(
                    'export_state',
                    Table::TYPE_SMALLINT,
                    null,
                    [
                        'nullable' => false,
                        'default' => FeedProduct::STATE_NOT_EXPORTED,
                    ],
                    'Export State'
                )
                ->addColumn(
                    'child_export_state',
                    Table::TYPE_SMALLINT,
                    null,
                    [
                        'nullable' => false,
                        'default' => FeedProduct::STATE_NOT_EXPORTED,
                    ],
                    'Export State (as a Child)'
                )
                ->addColumn(
                    'export_retention_started_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    [],
                    'Export Retention Started At'
                )
                ->addColumn(
                    'export_state_refreshed_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    [],
                    'Export State Refreshed At'
                )
                ->addColumn(
                    'export_state_refresh_state',
                    Table::TYPE_SMALLINT,
                    null,
                    [
                        'nullable' => false,
                        'default' => FeedRefresher::REFRESH_STATE_REQUIRED,
                    ],
                    'Export State Refresh State'
                )
                ->addColumn(
                    'export_state_refresh_state_updated_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'nullable' => false,
                        'default' => Table::TIMESTAMP_INIT,
                    ],
                    'Export State Refresh State Updated At'
                )
                ->addIndex(
                    $setup->getIdxName($feedProductTableName, 'selected_category_id'),
                    'selected_category_id'
                )
                ->addIndex(
                    $setup->getIdxName($feedProductTableName, 'export_state'),
                    'export_state'
                )
                ->addIndex(
                    $setup->getIdxName($feedProductTableName, 'child_export_state'),
                    'child_export_state'
                )
                ->addIndex(
                    $setup->getIdxName($feedProductTableName, 'export_state_refresh_state'),
                    'export_state_refresh_state'
                )
                ->addForeignKey(
                    $setup->getFkName($feedProductTableName, 'product_id', $catalogProductTableName, 'entity_id'),
                    'product_id',
                    $setup->getTable($catalogProductTableName),
                    'entity_id',
                    Table::ACTION_CASCADE
                )
                ->addForeignKey(
                    $setup->getFkName($feedProductTableName, 'store_id', $accountStoreTableName, 'store_id'),
                    'store_id',
                    $setup->getTable($accountStoreTableName),
                    'store_id',
                    Table::ACTION_CASCADE
                )
                ->addForeignKey(
                    $setup->getFkName(
                        $feedProductTableName,
                        'selected_category_id',
                        $catalogCategoryTableName,
                        'entity_id'
                    ),
                    'selected_category_id',
                    $setup->getTable($catalogCategoryTableName),
                    'entity_id',
                    Table::ACTION_SET_NULL
                )
                ->setComment('Shopping Feed Feed Product');

            $connection->createTable($table);
        }

        /**
         * Create "sfm_feed_product_section_type" table
         */
        $feedSectionTypeTableName = 'sfm_feed_product_section_type';

        if (!$setup->tableExists($feedSectionTypeTableName)) {
            $table = $connection->newTable($setup->getTable($feedSectionTypeTableName))
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
                    $setup->getIdxName($feedSectionTypeTableName, 'code', AdapterInterface::INDEX_TYPE_UNIQUE),
                    'code',
                    [ 'type' => AdapterInterface::INDEX_TYPE_UNIQUE ]
                )
                ->setComment('Shopping Feed Feed Product Section Type');

            $connection->createTable($table);
        }

        /**
         * Create "sfm_feed_product_section" table
         */
        $feedSectionTableName = 'sfm_feed_product_section';

        if (!$setup->tableExists($feedSectionTableName)) {
            $table = $connection->newTable($setup->getTable($feedSectionTableName))
                ->addColumn(
                    'type_id',
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
                    'product_id',
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
                    'store_id',
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
                    'data',
                    Table::TYPE_TEXT,
                    65535,
                    [
                        'nullable' => false,
                        'default' => '',
                    ],
                    'Data'
                )
                ->addColumn(
                    'refreshed_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    [],
                    'Refreshed At'
                )
                ->addColumn(
                    'refresh_state',
                    Table::TYPE_SMALLINT,
                    null,
                    [
                        'nullable' => false,
                        'default' => FeedRefresher::REFRESH_STATE_REQUIRED,
                    ],
                    'Refresh State'
                )
                ->addColumn(
                    'refresh_state_updated_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'nullable' => false,
                        'default' => Table::TIMESTAMP_INIT,
                    ],
                    'Refresh State Updated At'
                )
                ->addIndex(
                    $setup->getIdxName($feedSectionTableName, 'refresh_state'),
                    'refresh_state'
                )
                ->addForeignKey(
                    $setup->getFkName($feedSectionTableName, 'type_id', $feedSectionTypeTableName, 'type_id'),
                    'type_id',
                    $setup->getTable($feedSectionTypeTableName),
                    'type_id',
                    Table::ACTION_CASCADE
                )
                ->addForeignKey(
                    $setup->getFkName($feedSectionTableName, 'product_id', $feedProductTableName, 'product_id'),
                    'product_id',
                    $setup->getTable($feedProductTableName),
                    'product_id',
                    Table::ACTION_CASCADE
                )
                ->addForeignKey(
                    $setup->getFkName($feedSectionTableName, 'store_id', $accountStoreTableName, 'store_id'),
                    'store_id',
                    $setup->getTable($accountStoreTableName),
                    'store_id',
                    Table::ACTION_CASCADE
                )
                ->setComment('Shopping Feed Feed Product Section');

            $connection->createTable($table);
        }

        $setup->endSetup();
    }
}
