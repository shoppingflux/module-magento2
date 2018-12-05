<?php

namespace ShoppingFeed\Manager\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb as BaseDb;
use Magento\Framework\Model\ResourceModel\Db\Context as DbContext;
use ShoppingFeed\Manager\Model\TimeHelper;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\ProductFilterApplier;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product\SectionFilterApplier;
use ShoppingFeed\Manager\Model\ResourceModel\Table\Dictionary as TableDictionary;

abstract class AbstractDb extends BaseDb
{
    use WithSerializedData;

    const DATA_OBJECT_FIELD_NAMES = [];

    /**
     * @var TimeHelper
     */
    protected $timeHelper;

    /**
     * @var TableDictionary
     */
    protected $tableDictionary;

    /**
     * @var ProductFilterApplier
     */
    protected $productFilterApplier;

    /**
     * @var SectionFilterApplier
     */
    protected $sectionFilterApplier;

    /**
     * @param DbContext $context
     * @param TimeHelper $timeHelper
     * @param TableDictionary $tableDictionary
     * @param ProductFilterApplier $productFilterApplier
     * @param SectionFilterApplier $sectionFilterApplier
     * @param string|null $connectionName
     */
    public function __construct(
        DbContext $context,
        TimeHelper $timeHelper,
        TableDictionary $tableDictionary,
        ProductFilterApplier $productFilterApplier,
        SectionFilterApplier $sectionFilterApplier,
        $connectionName = null
    ) {
        $this->timeHelper = $timeHelper;
        $this->tableDictionary = $tableDictionary;
        $this->productFilterApplier = $productFilterApplier;
        $this->sectionFilterApplier = $sectionFilterApplier;
        parent::__construct($context, $connectionName);
    }

    protected function _getLoadSelect($field, $value, $object)
    {
        if (!is_array($field) || !is_array($value)) {
            return parent::_getLoadSelect($field, $value, $object);
        }
        
        $connection = $this->getConnection();
        $mainTable = $this->getMainTable();
        $select = $connection->select()->from($mainTable);
        
        foreach ($field as $subFieldName) {
            $subField = $connection->quoteIdentifier(sprintf('%s.%s', $mainTable, $subFieldName));
            $subValue = array_shift ($value);
            $select->where($subField . ' = ?', $subValue);
            
        }

        return $select;
    }

    protected function _prepareDataForSave(AbstractModel $object)
    {
        return $this->prepareDataForSaveWithSerialized(
            $object,
            static::DATA_OBJECT_FIELD_NAMES,
            function () use ($object) {
                return parent::_prepareDataForSave($object);
            }
        );
    }

    protected function prepareDataForUpdate($object)
    {
        return $this->prepareDataForUpdateWithSerialized(
            $object,
            static::DATA_OBJECT_FIELD_NAMES,
            function () use ($object) {
                return parent::prepareDataForUpdate($object);
            }
        );
    }
}
