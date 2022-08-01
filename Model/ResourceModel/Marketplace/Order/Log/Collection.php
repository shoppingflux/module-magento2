<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Log;

use Magento\Framework\DB\Select;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\LogInterface;
use ShoppingFeed\Manager\Model\Marketplace\Order\Log;
use ShoppingFeed\Manager\Model\ResourceModel\AbstractCollection;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Log as LogResource;

/**
 * @method LogResource getResource()
 */
class Collection extends AbstractCollection
{
    protected $_idFieldName = LogInterface::LOG_ID;

    /**
     * @var int|null
     */
    private $totalLogCount = null;

    /**
     * @var int|null
     */
    private $totalOrderCount = null;

    protected function _construct()
    {
        $this->_init(Log::class, LogResource::class);
    }

    public function clear()
    {
        $this->totalLogCount = 0;
        $this->totalOrderCount = 0;

        return parent::clear();
    }

    /**
     * @param int|int[] $orderIds
     * @return $this
     */
    public function addOrderIdFilter($orderIds)
    {
        $this->addFieldToFilter(LogInterface::ORDER_ID, [ 'in' => $this->prepareIdFilterValue($orderIds) ]);

        return $this;
    }

    /**
     * @param bool $isRead
     * @return $this
     */
    public function addReadFilter($isRead = true)
    {
        $this->addFieldToFilter(LogInterface::IS_READ, $isRead);

        return $this;
    }

    /**
     * @return LogInterface[][]
     */
    public function getLogsByOrder()
    {
        return $this->getGroupedItems([ LogInterface::ORDER_ID ], true);
    }

    private function loadCounts()
    {
        if ((null !== $this->totalLogCount) && (null !== $this->totalOrderCount)) {
            return;
        }

        $countSelect = clone $this->getSelect();

        $countSelect->reset(Select::COLUMNS);
        $countSelect->reset(Select::GROUP);
        $countSelect->reset(Select::ORDER);
        $countSelect->reset(Select::LIMIT_COUNT);
        $countSelect->reset(Select::LIMIT_OFFSET);

        $countSelect->columns(
            [
                'log_count' => new \Zend_Db_Expr('COUNT(DISTINCT main_table.log_id)'),
                'order_count' => new \Zend_Db_Expr('COUNT(DISTINCT main_table.order_id)'),
            ]
        );

        $result = $this->getConnection()->fetchRow($countSelect);

        $this->totalLogCount = (int) $result['log_count'] ?? 0;
        $this->totalOrderCount = (int) $result['order_count'] ?? 0;
    }

    public function getSize()
    {
        $this->loadCounts();

        return $this->totalLogCount;
    }

    /**
     * @return int
     */
    public function getOrderCount()
    {
        $this->loadCounts();

        return $this->totalOrderCount;
    }
}
