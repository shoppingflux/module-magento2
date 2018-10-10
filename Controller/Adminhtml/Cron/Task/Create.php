<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Cron\Task;

use ShoppingFeed\Manager\Controller\Adminhtml\Cron\TaskAction;

class Create extends TaskAction
{
    public function execute()
    {
        $this->_forward('edit');
    }
}
