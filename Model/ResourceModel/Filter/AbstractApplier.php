<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Filter;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context as DbContext;
use Magento\Framework\DB\Select as DbSelect;
use ShoppingFeed\Manager\Model\AbstractFilter;
use ShoppingFeed\Manager\Model\Time\Helper as TimeHelper;


abstract class AbstractApplier extends AbstractDb
{
    /**
     * @var TimeHelper
     */
    protected $timeHelper;

    /**
     * @param DbContext $context
     * @param TimeHelper $timeHelper
     * @param string|null $connectionName
     */
    public function __construct(DbContext $context, TimeHelper $timeHelper, string $connectionName = null)
    {
        $this->timeHelper = $timeHelper;
        parent::__construct($context, $connectionName);
    }

    /**
     * @param string $fieldName
     * @param string $condition
     * @param mixed|null $value
     * @param string|null $tableAlias
     * @return string
     */
    protected function getQuotedCondition($fieldName, $condition, $value = null, $tableAlias = null)
    {
        $connection = $this->getConnection();

        if (null !== $tableAlias) {
            $fieldName = $connection->quoteIdentifier($tableAlias . '.' . $fieldName);
        }

        $condition = $fieldName . ' ' . $condition;

        if (null !== $value) {
            $condition = $connection->quoteInto($condition, $value);
        }

        return $condition;
    }

    /**
     * @param AbstractFilter $filter
     * @param string|null $tableAlias
     */
    abstract public function getFilterConditions(AbstractFilter $filter, $tableAlias = null);

    /**
     * @param DbSelect $select
     * @param AbstractFilter $filter
     * @param string|null $tableAlias
     * @return $this
     */
    public function applyFilterToDbSelect(DbSelect $select, AbstractFilter $filter, $tableAlias = null)
    {
        foreach ($this->getFilterConditions($filter, $tableAlias) as $condition) {
            $select->where($condition);
        }

        return $this;
    }
}
