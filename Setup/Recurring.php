<?php

namespace ShoppingFeed\Manager\Setup;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use ShoppingFeed\Manager\Api\Account\StoreRepositoryInterface;
use ShoppingFeed\Manager\Model\Account\Store\ConfigManager as StoreConfigManager;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\CollectionFactory as StoreCollectionFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Table\Dictionary as TableDictionary;

class Recurring implements InstallSchemaInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

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
     * @var int
     */
    private $salesOrderGridBatchSize;

    /**
     * @param StoreConfigManager $storeConfigManager
     * @param StoreRepositoryInterface $storeRepository
     * @param StoreCollectionFactory $storeCollectionFactory
     * @param ModuleDataSetupInterface|null $moduleDataSetup
     * @param TableDictionary|null $tableDictionary
     * @param int $salesOrderGridBatchSize
     */
    public function __construct(
        StoreConfigManager $storeConfigManager,
        StoreRepositoryInterface $storeRepository,
        StoreCollectionFactory $storeCollectionFactory,
        ModuleDataSetupInterface $moduleDataSetup = null,
        TableDictionary $tableDictionary = null,
        $salesOrderGridBatchSize = 1000
    ) {
        $this->storeConfigManager = $storeConfigManager;
        $this->storeRepository = $storeRepository;
        $this->storeCollectionFactory = $storeCollectionFactory;
        $objectManager = ObjectManager::getInstance();
        $this->tableDictionary = $tableDictionary ?: $objectManager->get(TableDictionary::class);
        $this->moduleDataSetup = $moduleDataSetup ?: $objectManager->get(ModuleDataSetupInterface::class);
        $this->salesOrderGridBatchSize = max(1, (int) $salesOrderGridBatchSize);
    }

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->upgradeStoresData($context);
        $this->complementSalesOrderGridData();
    }

    private function upgradeStoresData(ModuleContextInterface $context)
    {
        $moduleVersion = $context->getVersion();

        if (!empty($moduleVersion)) {
            $storeCollection = $this->storeCollectionFactory->create();

            foreach ($storeCollection as $store) {
                if ($this->storeConfigManager->upgradeStoreData($store, $moduleVersion)) {
                    $this->storeRepository->save($store);
                }
            }
        }
    }

    private function complementSalesOrderGridData()
    {
        $connection = $this->moduleDataSetup->getConnection();

        $fromOrderId = (int) $connection->fetchOne(
            $connection
                ->select()
                ->from(
                    [ 'main_table' => $this->tableDictionary->getSalesOrderGridTableName() ],
                    'entity_id'
                )
                ->joinInner(
                    [ 'sfm_table' => $this->tableDictionary->getMarketplaceOrderTableName() ],
                    'main_table.entity_id = sfm_table.sales_order_id',
                    []
                )
                ->where('(sfm_marketplace_name IS NULL) OR (sfm_marketplace_order_number IS NULL)')
                ->order('entity_id DESC')
                ->limit(1)
        );

        while ($fromOrderId > 0) {
            $toOrderId = max(1, $fromOrderId - $this->salesOrderGridBatchSize);

            $incompleteRowsSelect = $connection
                ->select()
                ->joinInner(
                    [ 'sfm_table' => $this->tableDictionary->getMarketplaceOrderTableName() ],
                    'main_table.entity_id = sfm_table.sales_order_id',
                    [
                        'sfm_marketplace_name' => 'marketplace_name',
                        'sfm_marketplace_order_number' => 'marketplace_order_number',
                    ]
                )
                ->where('main_table.entity_id >= ?', $toOrderId)
                ->where('main_table.entity_id <= ?', $fromOrderId)
                ->where('(sfm_marketplace_name IS NULL) OR (sfm_marketplace_order_number IS NULL)');

            $connection->query(
                $connection->updateFromSelect(
                    $incompleteRowsSelect,
                    [ 'main_table' => $this->tableDictionary->getSalesOrderGridTableName() ]
                )
            );

            $fromOrderId = (int) $connection->fetchOne(
                $connection
                    ->select()
                    ->from(
                        [ 'main_table' => $this->tableDictionary->getSalesOrderGridTableName() ],
                        'entity_id'
                    )
                    ->joinInner(
                        [ 'sfm_table' => $this->tableDictionary->getMarketplaceOrderTableName() ],
                        'main_table.entity_id = sfm_table.sales_order_id',
                        []
                    )
                    ->where('main_table.entity_id < ?', $toOrderId)
                    ->where('(sfm_marketplace_name IS NULL) OR (sfm_marketplace_order_number IS NULL)')
                    ->order('entity_id DESC')
                    ->limit(1)
            );
        }
    }
}
