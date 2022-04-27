<?php

namespace ShoppingFeed\Manager\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use ShoppingFeed\Manager\Api\Data\Cron\TaskInterface as CronTaskInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Table\Dictionary as TableDictionary;

class CreateDefaultCronTasks implements DataPatchInterface
{
    /**
     * @var TableDictionary
     */
    private $tableDictionary;

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @param TableDictionary $tableDictionary
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(TableDictionary $tableDictionary, ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->tableDictionary = $tableDictionary;
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * @return array
     */
    private function getDefaultCronTasksData()
    {
        $emptyConfiguration = json_encode([], JSON_FORCE_OBJECT);

        return [
            [
                CronTaskInterface::NAME => __('Synchronize Product List'),
                CronTaskInterface::COMMAND_CODE => 'feed/sync_product_list',
                CronTaskInterface::COMMAND_CONFIGURATION => $emptyConfiguration,
                CronTaskInterface::SCHEDULE_TYPE => CronTaskInterface::SCHEDULE_TYPE_EVERY_HOUR,
                CronTaskInterface::CRON_EXPRESSION => '',
                CronTaskInterface::IS_ACTIVE => 1,
            ],
            [
                CronTaskInterface::NAME => __('Force Automatic Data Refresh'),
                CronTaskInterface::COMMAND_CODE => 'feed/force_automatic_refresh',
                CronTaskInterface::COMMAND_CONFIGURATION => $emptyConfiguration,
                CronTaskInterface::SCHEDULE_TYPE => CronTaskInterface::SCHEDULE_TYPE_EVERY_15_MINUTES,
                CronTaskInterface::CRON_EXPRESSION => '',
                CronTaskInterface::IS_ACTIVE => 1,
            ],
            [
                CronTaskInterface::NAME => __('Refresh Data'),
                CronTaskInterface::COMMAND_CODE => 'feed/refresh',
                CronTaskInterface::COMMAND_CONFIGURATION => $emptyConfiguration,
                CronTaskInterface::SCHEDULE_TYPE => CronTaskInterface::SCHEDULE_TYPE_EVERY_30_MINUTES,
                CronTaskInterface::CRON_EXPRESSION => '',
                CronTaskInterface::IS_ACTIVE => 1,
            ],
            [
                CronTaskInterface::NAME => __('Export Feed'),
                CronTaskInterface::COMMAND_CODE => 'feed/export',
                CronTaskInterface::COMMAND_CONFIGURATION => $emptyConfiguration,
                CronTaskInterface::SCHEDULE_TYPE => CronTaskInterface::SCHEDULE_TYPE_EVERY_HOUR,
                CronTaskInterface::CRON_EXPRESSION => '',
                CronTaskInterface::IS_ACTIVE => 1,
            ],
            [
                CronTaskInterface::NAME => __('Fetch Marketplace Orders'),
                CronTaskInterface::COMMAND_CODE => 'orders/fetch_marketplace_orders',
                CronTaskInterface::COMMAND_CONFIGURATION => $emptyConfiguration,
                CronTaskInterface::SCHEDULE_TYPE => CronTaskInterface::SCHEDULE_TYPE_EVERY_15_MINUTES,
                CronTaskInterface::CRON_EXPRESSION => '',
                CronTaskInterface::IS_ACTIVE => 1,
            ],
            [
                CronTaskInterface::NAME => __('Import Orders'),
                CronTaskInterface::COMMAND_CODE => 'orders/import_sales_orders',
                CronTaskInterface::COMMAND_CONFIGURATION => $emptyConfiguration,
                CronTaskInterface::SCHEDULE_TYPE => CronTaskInterface::SCHEDULE_TYPE_EVERY_15_MINUTES,
                CronTaskInterface::CRON_EXPRESSION => '',
                CronTaskInterface::IS_ACTIVE => 1,
            ],
            [
                CronTaskInterface::NAME => __('Send State Updates'),
                CronTaskInterface::COMMAND_CODE => 'orders/send_state_updates',
                CronTaskInterface::COMMAND_CONFIGURATION => $emptyConfiguration,
                CronTaskInterface::SCHEDULE_TYPE => CronTaskInterface::SCHEDULE_TYPE_EVERY_30_MINUTES,
                CronTaskInterface::CRON_EXPRESSION => '',
                CronTaskInterface::IS_ACTIVE => 1,
            ],
        ];
    }

    public function apply()
    {
        $connection = $this->moduleDataSetup->getConnection();
        $cronTaskTableName = $this->tableDictionary->getCronTaskTableName();

        $cronTaskCount = (int) $connection->fetchOne(
            $connection->select()
                ->from($cronTaskTableName, new \Zend_Db_Expr('COUNT(*)'))
        );

        if (0 === $cronTaskCount) {
            $connection->insertMultiple(
                $cronTaskTableName,
                $this->getDefaultCronTasksData()
            );
        }
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
