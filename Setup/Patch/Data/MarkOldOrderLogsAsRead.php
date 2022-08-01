<?php

namespace ShoppingFeed\Manager\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\LogInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Table\Dictionary as TableDictionary;

class MarkOldOrderLogsAsRead implements DataPatchInterface
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

    public function apply()
    {
        $connection = $this->moduleDataSetup->getConnection();

        $connection->update(
            $this->tableDictionary->getMarketplaceOrderLogTableName(),
            [ LogInterface::IS_READ => 1 ]
        );
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
