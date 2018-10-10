<?php

namespace ShoppingFeed\Manager\Plugin\Cron;

use Magento\Cron\Model\ConfigInterface;
use ShoppingFeed\Manager\Cron\RunTask;
use ShoppingFeed\Manager\Model\Cron\Task;
use ShoppingFeed\Manager\Model\ResourceModel\Cron\Task\CollectionFactory as TaskCollectionFactory;

class ConfigPlugin
{
    const CRON_GROUP = 'default';

    /**
     * @var TaskCollectionFactory
     */
    private $taskCollectionFactory;

    /**
     * @param TaskCollectionFactory $taskCollectionFactory
     */
    public function __construct(TaskCollectionFactory $taskCollectionFactory)
    {
        $this->taskCollectionFactory = $taskCollectionFactory;
    }

    /**
     * @param ConfigInterface $subject
     * @param array $jobs
     * @return array
     */
    public function afterGetJobs(ConfigInterface $subject, $jobs)
    {
        if (is_array($jobs)) {
            $taskCollection = $this->taskCollectionFactory->create();
            $taskCollection->addActiveFilter(true);

            if (!isset($jobs[static::CRON_GROUP])) {
                $jobs[static::CRON_GROUP] = [];
            }

            /** @var Task $task */
            foreach ($taskCollection as $task) {
                if ($cronExpression = $task->getCronExpression()) {
                    $jobName = $task->getUniqueJobName();

                    $jobs[static::CRON_GROUP][$jobName] = [
                        'name' => $jobName,
                        'instance' => RunTask::class,
                        'method' => sprintf(RunTask::BASE_TASK_RUN_METHOD_NAME, $task->getId()),
                        'schedule' => $cronExpression,
                    ];
                }
            }
        }

        return $jobs;
    }
}
