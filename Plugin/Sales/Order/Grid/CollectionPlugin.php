<?php

namespace ShoppingFeed\Manager\Plugin\Sales\Order\Grid;

use Magento\Framework\DB\Select as DbSelect;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OrderGridCollection;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Table\Dictionary as TableDictionary;

class CollectionPlugin
{
    const COLLECTION_FLAG_JOINED_SFM_TABLES = '_sfm_joined_tables_';
    const SFM_ORDER_TABLE_ALIAS = '_sfm_order_table';
    const SFM_FIELD_ALIAS_PREFIX = 'sfm_';

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
     * @return string[]
     */
    public function getJoinableMarketplaceOrderFieldNames()
    {
        return [
            MarketplaceOrderInterface::MARKETPLACE_ORDER_NUMBER,
            MarketplaceOrderInterface::MARKETPLACE_NAME,
        ];
    }

    /**
     * @param string $fieldName
     * @return string
     */
    public function getJoinedFieldAlias($fieldName)
    {
        return self::SFM_FIELD_ALIAS_PREFIX . $fieldName;
    }

    /**
     * @param OrderGridCollection $collection
     */
    public function disambiguateOrderGridCollectionFilters(OrderGridCollection $collection)
    {
        $collection->addFilterToMap('store_id', 'main_table.store_id');
        $collection->addFilterToMap('created_at', 'main_table.created_at');
        $collection->addFilterToMap('updated_at', 'main_table.updated_at');
    }

    /**
     * @param OrderGridCollection $subject
     * @param DbSelect|null $select
     * @return DbSelect|null
     */
    public function afterGetSelect(OrderGridCollection $subject, $select)
    {
        if ((null !== $select) && !$this->isAppliedToOrderGridCollection($subject)) {
            $this->disambiguateOrderGridCollectionFilters($subject);

            $subject->setFlag(self::COLLECTION_FLAG_JOINED_SFM_TABLES, true);
            $connection = $subject->getResource()->getConnection();
            $tableAlias = self::SFM_ORDER_TABLE_ALIAS;
            $joinedFields = [];

            foreach ($this->getJoinableMarketplaceOrderFieldNames() as $fieldName) {
                $fieldAlias = $this->getJoinedFieldAlias($fieldName);
                $joinedFields[$fieldAlias] = $fieldName;
                $subject->addFilterToMap($fieldAlias, $tableAlias . '.' . $fieldName);
            }

            $subject->getSelect()
                ->joinLeft(
                    [ $tableAlias => $this->tableDictionary->getMarketplaceOrderTableName() ],
                    $connection->quoteIdentifier('main_table.entity_id')
                    . '='
                    . $connection->quoteIdentifier($tableAlias . '.' . MarketplaceOrderInterface::SALES_ORDER_ID),
                    $joinedFields
                );
        }

        return $select;
    }

    /**
     * @param OrderGridCollection $collection
     * @return bool
     */
    public function isAppliedToOrderGridCollection(OrderGridCollection $collection)
    {
        return $collection->hasFlag(self::COLLECTION_FLAG_JOINED_SFM_TABLES);
    }
}
