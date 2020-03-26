<?php

namespace ShoppingFeed\Manager\Setup;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\AttributeFactory as CustomerAttributeResourceFactory;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use ShoppingFeed\Manager\Api\Data\Cron\TaskInterface as CronTaskInterface;
use ShoppingFeed\Manager\Api\Account\StoreRepositoryInterface;
use ShoppingFeed\Manager\Model\Account\Store\ConfigManager as StoreConfigManager;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\CollectionFactory as StoreCollectionFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Table\Dictionary as TableDictionary;
use ShoppingFeed\Manager\Model\Sales\Order\Customer\Importer as CustomerImporter;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var TableDictionary
     */
    private $tableDictionary;

    /**
     * @var CustomerAttributeResourceFactory
     */
    private $customerAttributeResourceFactory;

    /**
     * @var CustomerSetupFactory
     */
    private $customerSetupFactory;

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
     * @param CustomerAttributeResourceFactory $customerAttributeResourceFactory
     * @param CustomerSetupFactory $customerSetupFactory
     * @param StoreConfigManager $storeConfigManager
     * @param StoreRepositoryInterface $storeRepository
     * @param StoreCollectionFactory $storeCollectionFactory
     */
    public function __construct(
        TableDictionary $tableDictionary,
        CustomerAttributeResourceFactory $customerAttributeResourceFactory,
        CustomerSetupFactory $customerSetupFactory,
        StoreConfigManager $storeConfigManager,
        StoreRepositoryInterface $storeRepository,
        StoreCollectionFactory $storeCollectionFactory
    ) {
        $this->tableDictionary = $tableDictionary;
        $this->customerAttributeResourceFactory = $customerAttributeResourceFactory;
        $this->customerSetupFactory = $customerSetupFactory;
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

        if (empty($moduleVersion) || (version_compare($moduleVersion, '0.31.0') < 0)) {
            $this->addFromShoppingFeedCustomerAttribute($setup);
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

    /**
     * @param ModuleDataSetupInterface $setup
     * @throws LocalizedException
     */
    public function addFromShoppingFeedCustomerAttribute(ModuleDataSetupInterface $setup)
    {
        $customerSetup = $this->customerSetupFactory->create([ 'setup' => $setup ]);

        $customerSetup->addAttribute(
            Customer::ENTITY,
            CustomerImporter::CUSTOMER_FROM_SHOPPING_ATTRIBUTE_CODE,
            [
                'type' => 'int',
                'label' => 'From Shopping Feed',
                'input' => 'select',
                'required' => false,
                'visible' => true,
                'system' => false,
                'user_defined' => true,
                'default' => false,
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'position' => 1000,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
            ]
        );

        $attribute = $customerSetup->getEavConfig()
            ->getAttribute(
                Customer::ENTITY,
                CustomerImporter::CUSTOMER_FROM_SHOPPING_ATTRIBUTE_CODE
            );

        $attributeSetId = $customerSetup->getDefaultAttributeSetId(Customer::ENTITY);
        $attributeGroupId = $customerSetup->getDefaultAttributeGroupId(Customer::ENTITY, $attributeSetId);

        $attribute->addData(
            [
                'attribute_set_id' => $attributeSetId,
                'attribute_group_id' => $attributeGroupId,
                'used_in_forms' => [ 'adminhtml_customer' ]
            ]
        );

        $customerAttributeResource = $this->customerAttributeResourceFactory->create();
        $customerAttributeResource->save($attribute);
    }
}
