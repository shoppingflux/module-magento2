<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Log;

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

    protected function _construct()
    {
        $this->_init(Log::class, LogResource::class);
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
     * @return LogInterface[][]
     */
    public function getLogsByOrder()
    {
        return $this->getGroupedItems([ LogInterface::ORDER_ID ], true);
    }
}
