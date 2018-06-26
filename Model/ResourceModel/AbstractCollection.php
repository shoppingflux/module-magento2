<?php

namespace ShoppingFeed\Manager\Model\ResourceModel;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection as BaseCollection;
use Psr\Log\LoggerInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Table\Dictionary as TableDictionary;


class AbstractCollection extends BaseCollection
{
    /**
     * @var TableDictionary
     */
    protected $tableDictionary;

    /**
     * @param EntityFactoryInterface $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param EventManagerInterface $eventManager
     * @param TableDictionary $tableDictionary
     * @param AdapterInterface|null $connection
     * @param AbstractDb|null $resource
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        EventManagerInterface $eventManager,
        TableDictionary $tableDictionary,
        AdapterInterface $connection = null,
        AbstractDb $resource = null
    ) {
        $this->tableDictionary = $tableDictionary;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    /**
     * @param int|int[] $filterValue
     * @return int[]
     */
    protected function prepareIdFilterValue($filterValue)
    {
        if (is_array($filterValue)) {
            $filterValue = array_map('intval', $filterValue);
        } else {
            $filterValue = [ (int) $filterValue ];
        }

        return $filterValue;
    }

    /**
     * @return array
     */
    public function getLoadedIds()
    {
        $loadedIds = [];

        foreach ($this->getItems() as $item) {
            $loadedIds[] = $this->_getItemId($item);
        }

        return $loadedIds;
    }

    /**
     * @param string[] $groupFields
     * @param bool $singleAsArray
     * @return array
     */
    public function getGroupedItems(array $groupFields, $singleAsArray = false)
    {
        $items = $this->getItems();
        $groupedItems = [];

        foreach ($items as $item) {
            $itemGroup = &$groupedItems;

            foreach ($groupFields as $fieldName) {
                if ($item->hasData($fieldName)) {
                    $itemValue = $item->getDataByKey($fieldName);

                    if (!isset($itemGroup[$itemValue])) {
                        $itemGroup[$itemValue] = [];
                    }

                    $itemGroup = &$itemGroup[$itemValue];
                }
            }

            if (!is_array($itemGroup)) {
                $itemGroup = [ $itemGroup, $item ];
            } elseif (empty($itemGroup) && !$singleAsArray) {
                $itemGroup = $item;
            } else {
                $itemGroup[] = $item;
            }
        }

        return $groupedItems;
    }
}
