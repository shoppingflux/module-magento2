<?php

namespace ShoppingFeed\Manager\Api\Cron;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Api\Data\Cron\TaskInterface;

/**
 * @api
 */
interface TaskRepositoryInterface
{
    /**
     * @param TaskInterface $task
     * @return TaskInterface
     * @throws CouldNotSaveException
     */
    public function save(TaskInterface $task);

    /**
     * @param int $taskId
     * @return TaskInterface
     * @throws NoSuchEntityException
     */
    public function getById($taskId);

    /**
     * @param TaskInterface $task
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(TaskInterface $task);

    /**
     * @param int $taskId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById($taskId);
}
