<?php

namespace ShoppingFeed\Manager\Cron;

use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Api\Cron\TaskRepositoryInterface;
use ShoppingFeed\Manager\Model\Cron\Task\Runner as TaskRunner;

class RunTask
{
    const BASE_TASK_RUN_METHOD_NAME = 'runTask%d';

    /**
     * @var TaskRepositoryInterface
     */
    private $taskRepository;

    /**
     * @var TaskRunner
     */
    private $taskRunner;

    /**
     * @param TaskRepositoryInterface $taskRepository
     * @param TaskRunner $taskRunner
     */
    public function __construct(TaskRepositoryInterface $taskRepository, TaskRunner $taskRunner)
    {
        $this->taskRepository = $taskRepository;
        $this->taskRunner = $taskRunner;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @throws LocalizedException
     */
    public function __call($name, $arguments)
    {
        if (preg_match('/^runTask([0-9]+)$/', $name, $matches)) {
            $task = $this->taskRepository->getById((int) $matches[1]);
            $this->taskRunner->runTask($task);
            return;
        }

        throw new LocalizedException(__('Invalid method %1::%2', [ get_class($this), $name ]));
    }
}
