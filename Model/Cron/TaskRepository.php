<?php

namespace ShoppingFeed\Manager\Model\Cron;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Api\Cron\TaskRepositoryInterface;
use ShoppingFeed\Manager\Api\Data\Cron\TaskInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Cron\Task as TaskResource;
use ShoppingFeed\Manager\Model\ResourceModel\Cron\TaskFactory as TaskResourceFactory;

class TaskRepository implements TaskRepositoryInterface
{
    /**
     * @var TaskResource
     */
    private $taskResource;

    /**
     * @var TaskFactory
     */
    private $taskFactory;

    /**
     * @param TaskResourceFactory $taskResourceFactory
     * @param TaskFactory $taskFactory
     */
    public function __construct(TaskResourceFactory $taskResourceFactory, TaskFactory $taskFactory)
    {
        $this->taskResource = $taskResourceFactory->create();
        $this->taskFactory = $taskFactory;
    }

    public function save(TaskInterface $task)
    {
        try {
            $this->taskResource->save($task);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $task;
    }

    public function getById($taskId)
    {
        $task = $this->taskFactory->create();
        $this->taskResource->load($task, $taskId);

        if (!$task->getId()) {
            throw new NoSuchEntityException(__('Cron task for ID "%1" does not exist.', $taskId));
        }

        return $task;
    }

    public function delete(TaskInterface $task)
    {
        try {
            $this->taskResource->delete($task);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    public function deleteById($taskId)
    {
        return $this->delete($this->getById($taskId));
    }
}
