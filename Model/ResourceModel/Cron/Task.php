<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Cron;

use ShoppingFeed\Manager\Api\Data\Cron\TaskInterface;
use ShoppingFeed\Manager\Model\ResourceModel\AbstractDb;

class Task extends AbstractDb
{
    const DATA_OBJECT_FIELD_NAMES = [ TaskInterface::COMMAND_CONFIGURATION ];

    protected function _construct()
    {
        $this->_init('sfm_cron_task', TaskInterface::TASK_ID);
    }
}
