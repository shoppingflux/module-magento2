<?php

namespace ShoppingFeed\Manager\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\SalesRule\Model\Rule\Condition\Combine as BaseCombinedConditions;
use ShoppingFeed\Manager\Api\Data\Shipping\Method\RuleInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Table\Dictionary as TableDictionary;
use ShoppingFeed\Manager\Model\Shipping\Method\Rule\Condition\Combine as SfmCombinedConditions;

class FixShippingMethodRuleCombinedConditions implements DataPatchInterface
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
            $this->tableDictionary->getShippingMethodRuleTableName(),
            [
                RuleInterface::CONDITIONS_SERIALIZED => new \Zend_Db_Expr(
                    'REPLACE('
                    . RuleInterface::CONDITIONS_SERIALIZED
                    . ','
                    . $connection->quote('"' . BaseCombinedConditions::class . '"')
                    . ','
                    . $connection->quote('"' . SfmCombinedConditions::class . '"')
                    . ')'
                ),
            ]
        );

        $connection->update(
            $this->tableDictionary->getShippingMethodRuleTableName(),
            [
                RuleInterface::CONDITIONS_SERIALIZED => new \Zend_Db_Expr(
                    'REPLACE('
                    . RuleInterface::CONDITIONS_SERIALIZED
                    . ','
                    . $connection->quote('"' . addcslashes(BaseCombinedConditions::class, '\\') . '"')
                    . ','
                    . $connection->quote('"' . addcslashes(SfmCombinedConditions::class, '\\') . '"')
                    . ')'
                ),
            ]
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
