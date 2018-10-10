<?php

namespace ShoppingFeed\Manager\Api\Data\Cron;

use Magento\Framework\DataObject;

interface TaskInterface
{
    const SCHEDULE_TYPE_CUSTOM = 'custom';
    const SCHEDULE_TYPE_NEVER = 'never';
    const SCHEDULE_TYPE_EVERY_MINUTE = 'every_minute';
    const SCHEDULE_TYPE_EVERY_5_MINUTES = 'every_5_minutes';
    const SCHEDULE_TYPE_EVERY_15_MINUTES = 'every_15_minutes';
    const SCHEDULE_TYPE_EVERY_30_MINUTES = 'every_30_minutes';
    const SCHEDULE_TYPE_EVERY_HOUR = 'every_hour';
    const SCHEDULE_TYPE_EVERY_DAY_AT_4AM = 'every_day_at_4am';
    const SCHEDULE_TYPE_EVERY_MONDAY_AT_4AM = 'every_monday_at_4am';
    const SCHEDULE_TYPE_EVERY_MONTH_FIRST_DAY_AT_4AM = 'every_month_first_day_at_4am';

    /**#@+*/
    const TASK_ID = 'task_id';
    const NAME = 'name';
    const DESCRIPTION = 'description';
    const COMMAND_CODE = 'command_code';
    const COMMAND_CONFIGURATION = 'command_configuration';
    const SCHEDULE_TYPE = 'schedule_type';
    const CRON_EXPRESSION = 'cron_expression';
    const IS_ACTIVE = 'is_active';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    /**#@-*/

    /**
     * @return int|null
     */
    public function getId();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getDescription();

    /**
     * @return string
     */
    public function getCommandCode();

    /**
     * @return DataObject
     */
    public function getCommandConfiguration();

    /**
     * @return string
     */
    public function getScheduleType();

    /**
     * @return string|false
     */
    public function getCronExpression();

    /**
     * @return string
     */
    public function getUniqueJobName();

    /**
     * @return bool
     */
    public function isActive();

    /**
     * @return string
     */
    public function getCreatedAt();

    /**
     * @return string
     */
    public function getUpdatedAt();

    /**
     * @param int $id
     * @return TaskInterface
     */
    public function setId($id);

    /**
     * @param string $name
     * @return TaskInterface
     */
    public function setName($name);

    /**
     * @param string $description
     * @return TaskInterface
     */
    public function setDescription($description);

    /**
     * @param string $code
     * @return TaskInterface
     */
    public function setCommandCode($code);

    /**
     * @param DataObject $configuration
     * @return TaskInterface
     */
    public function setCommandConfiguration(DataObject $configuration);

    /**
     * @param string $scheduleType
     * @return TaskInterface
     */
    public function setScheduleType($scheduleType);

    /**
     * @param string $cronExpression
     * @return TaskInterface
     */
    public function setCronExpression($cronExpression);

    /**
     * @param bool $isActive
     * @return TaskInterface
     */
    public function setIsActive($isActive);

    /**
     * @param string $createdAt
     * @return TaskInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * @param string $updatedAt
     * @return TaskInterface
     */
    public function setUpdatedAt($updatedAt);
}
