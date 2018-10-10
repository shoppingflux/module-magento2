<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Cron\Task;

use ShoppingFeed\Manager\Controller\Adminhtml\Cron\TaskAction;

class Index extends TaskAction
{
    public function execute()
    {
        return $this->initPage();
    }
}
