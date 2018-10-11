<?php

namespace ShoppingFeed\Manager\Model\Cron\Task;

use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Api\Data\Cron\TaskInterface;
use ShoppingFeed\Manager\Model\CommandPoolInterface;

class Runner
{
    /**
     * @var CommandPoolInterface
     */
    private $commandPool;

    /**
     * @param CommandPoolInterface $commandPool
     */
    public function __construct(CommandPoolInterface $commandPool)
    {
        $this->commandPool = $commandPool;
    }

    /**
     * @param TaskInterface $task
     * @throws LocalizedException
     */
    public function runTask(TaskInterface $task)
    {
        $command = $this->commandPool->getCommandByCode($task->getCommandCode());
        $command->run($task->getCommandConfiguration());
    }
}
