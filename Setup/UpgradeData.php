<?php

namespace ShoppingFeed\Manager\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use ShoppingFeed\Manager\Api\Data\Cron\TaskInterface as CronTaskInterface;
use ShoppingFeed\Manager\Api\Account\StoreRepositoryInterface;
use ShoppingFeed\Manager\Model\Account\Store\ConfigManager as StoreConfigManager;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\CollectionFactory as StoreCollectionFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Table\Dictionary as TableDictionary;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var TableDictionary
     */
    private $tableDictionary;

    /**
     * @var StoreConfigManager
     */
    private $storeConfigManager;

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @var StoreCollectionFactory
     */
    private $storeCollectionFactory;

    /**
     * @param TableDictionary $tableDictionary
     * @param StoreConfigManager $storeConfigManager
     * @param StoreRepositoryInterface $storeRepository
     * @param StoreCollectionFactory $storeCollectionFactory
     */
    public function __construct(
        TableDictionary $tableDictionary,
        StoreConfigManager $storeConfigManager,
        StoreRepositoryInterface $storeRepository,
        StoreCollectionFactory $storeCollectionFactory
    ) {
        $this->tableDictionary = $tableDictionary;
        $this->storeConfigManager = $storeConfigManager;
        $this->storeRepository = $storeRepository;
        $this->storeCollectionFactory = $storeCollectionFactory;
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

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $moduleVersion = $context->getVersion();

        if (empty($moduleVersion) || (version_compare($moduleVersion, '0.5.0') < 0)) {
            $connection = $setup->getConnection();

            $connection->insertMultiple(
                $this->tableDictionary->getCronTaskTableName(),
                $this->getDefaultCronTasksData()
            );
        }

        if (!empty($moduleVersion)) {
            $storeCollection = $this->storeCollectionFactory->create();

            foreach ($storeCollection as $store) {
                if ($this->storeConfigManager->upgradeStoreData($store, $moduleVersion)) {
                    $this->storeRepository->save($store);
                }
            }
        }
    }
}
