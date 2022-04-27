<?php

namespace ShoppingFeed\Manager\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use ShoppingFeed\Manager\Api\Account\StoreRepositoryInterface;
use ShoppingFeed\Manager\Model\Account\Store\ConfigManager as StoreConfigManager;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\CollectionFactory as StoreCollectionFactory;

class Recurring implements InstallSchemaInterface
{
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
     * @param StoreConfigManager $storeConfigManager
     * @param StoreRepositoryInterface $storeRepository
     * @param StoreCollectionFactory $storeCollectionFactory
     */
    public function __construct(
        StoreConfigManager $storeConfigManager,
        StoreRepositoryInterface $storeRepository,
        StoreCollectionFactory $storeCollectionFactory
    ) {
        $this->storeConfigManager = $storeConfigManager;
        $this->storeRepository = $storeRepository;
        $this->storeCollectionFactory = $storeCollectionFactory;
    }

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
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
}
