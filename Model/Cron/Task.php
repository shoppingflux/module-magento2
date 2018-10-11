<?php

namespace ShoppingFeed\Manager\Model\Cron;

use Magento\Framework\Data\Collection\AbstractDb as AbstractCollection;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use ShoppingFeed\Manager\Api\Data\Cron\TaskInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Cron\Task as TaskResource;
use ShoppingFeed\Manager\Model\ResourceModel\Cron\Task\Collection as TaskCollection;

/**
 * @method TaskResource getResource()
 * @method TaskCollection getCollection()
 */
class Task extends AbstractModel implements TaskInterface
{
    const BASE_CRON_JOB_NAME = 'sfm_cron_task_%d';

    protected $_eventPrefix = 'shoppingfeed_manager_cron_task';
    protected $_eventObject = 'cron_task';

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param DataObjectFactory $dataObjectFactory
     * @param AbstractResource|null $resource
     * @param AbstractCollection|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        DataObjectFactory $dataObjectFactory,
        AbstractResource $resource = null,
        AbstractCollection $resourceCollection = null,
        array $data = []
    ) {
        $this->dataObjectFactory = $dataObjectFactory;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init(TaskResource::class);
    }

    public function getId()
    {
        $id = parent::getId();
        return empty($id) ? null : (int) $id;
    }

    public function getName()
    {
        return $this->getData(self::NAME);
    }

    public function getDescription()
    {
        return (string) $this->getData(self::DESCRIPTION);
    }

    public function getCommandCode()
    {
        return $this->getData(self::COMMAND_CODE);
    }

    public function getCommandConfiguration()
    {
        $data = $this->getData(self::COMMAND_CONFIGURATION);

        if (!$data instanceof DataObject) {
            $data = is_string($data) ? json_decode($data, true) : [];
            $data = $this->dataObjectFactory->create([ 'data' => is_array($data) ? $data : [] ]);
            $this->setData(self::COMMAND_CONFIGURATION, $data);
        }

        return $data;
    }

    public function getScheduleType()
    {
        return $this->getData(self::SCHEDULE_TYPE);
    }

    public function getCronExpression()
    {
        switch ($this->getScheduleType()) {
            case static::SCHEDULE_TYPE_CUSTOM:
                return trim($this->getData(self::CRON_EXPRESSION));
            case static::SCHEDULE_TYPE_EVERY_MINUTE:
                return '* * * * *';
            case static::SCHEDULE_TYPE_EVERY_5_MINUTES:
                return '*/5 * * * *';
            case static::SCHEDULE_TYPE_EVERY_15_MINUTES:
                return '*/15 * * * *';
            case static::SCHEDULE_TYPE_EVERY_30_MINUTES:
                return '*/30 * * * *';
            case static::SCHEDULE_TYPE_EVERY_HOUR:
                return '0 * * * *';
            case static::SCHEDULE_TYPE_EVERY_DAY_AT_4AM:
                return '0 4 * * *';
            case static::SCHEDULE_TYPE_EVERY_MONDAY_AT_4AM:
                return '0 4 * * mon';
            case static::SCHEDULE_TYPE_EVERY_MONTH_FIRST_DAY_AT_4AM:
                return '0 4 1 * *';
            case static::SCHEDULE_TYPE_NEVER:
            default:
                return false;
        }
    }

    public function getUniqueJobName()
    {
        return sprintf(static::BASE_CRON_JOB_NAME, $this->getId());
    }

    public function isActive()
    {
        return (bool) $this->getData(self::IS_ACTIVE);
    }

    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    public function setId($id)
    {
        return $this->setData(self::TASK_ID, (int) $id);
    }

    public function setName($name)
    {
        return $this->setData(self::NAME, trim($name));
    }

    public function setDescription($description)
    {
        return $this->setData(self::DESCRIPTION, (string) $description);
    }

    public function setCommandCode($code)
    {
        return $this->setData(self::COMMAND_CODE, trim($code));
    }

    public function setCommandConfiguration(DataObject $configuration)
    {
        return $this->setData(self::COMMAND_CONFIGURATION, $configuration);
    }

    public function setScheduleType($scheduleType)
    {
        $this->setData(self::SCHEDULE_TYPE, $scheduleType);

        if (self::SCHEDULE_TYPE_CUSTOM !== $scheduleType) {
            $this->setData(self::CRON_EXPRESSION, null);
        }

        return $this;
    }

    public function setCronExpression($cronExpression)
    {
        $this->setData(self::CRON_EXPRESSION, $cronExpression);
        return $this;
    }

    public function setIsActive($isActive)
    {
        return $this->setData(self::IS_ACTIVE, (bool) $isActive);
    }

    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
