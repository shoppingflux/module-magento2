<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Cron\Task;

use ShoppingFeed\Manager\Api\Data\Cron\TaskInterface;
use ShoppingFeed\Manager\Model\Cron\Task;
use ShoppingFeed\Manager\Model\ResourceModel\AbstractCollection;
use ShoppingFeed\Manager\Model\ResourceModel\Cron\Task as TaskResource;

/**
 * @method TaskResource getResource()
 */
class Collection extends AbstractCollection
{
    protected $_idFieldName = TaskInterface::TASK_ID;

    protected function _construct()
    {
        $this->_init(Task::class, TaskResource::class);
    }

    /**
     * @param int|int[] $taskIds
     * @return $this
     */
    public function addIdFilter($taskIds)
    {
        $this->addFieldToFilter(TaskInterface::TASK_ID, [ 'in' => $this->prepareIdFilterValue($taskIds) ]);
        return $this;
    }

    /**
     * @param bool $isActive
     * @return $this
     */
    public function addActiveFilter($isActive = true)
    {
        $this->addFieldToFilter(TaskInterface::IS_ACTIVE, $isActive ? 1 : 0);
        return $this;
    }
}
