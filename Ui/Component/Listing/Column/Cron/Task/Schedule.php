<?php

namespace ShoppingFeed\Manager\Ui\Component\Listing\Column\Cron\Task;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column as Column;
use ShoppingFeed\Manager\Api\Data\Cron\TaskInterface;
use ShoppingFeed\Manager\Model\Cron\Schedule\Type\Source as ScheduleTypeSource;

class Schedule extends Column
{
    /**
     * @var ScheduleTypeSource
     */
    private $scheduleTypeSource;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param ScheduleTypeSource $scheduleTypeSource
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        ScheduleTypeSource $scheduleTypeSource,
        array $components = [],
        array $data = []
    ) {
        $this->scheduleTypeSource = $scheduleTypeSource;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            $scheduleTypes = $this->scheduleTypeSource->toOptionHash();

            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item[$fieldName]) || isset($item[TaskInterface::SCHEDULE_TYPE])) {
                    $scheduleType = $item[$fieldName] ?? $item[TaskInterface::SCHEDULE_TYPE];

                    if ((TaskInterface::SCHEDULE_TYPE_CUSTOM === $scheduleType)
                        && isset($item[TaskInterface::CRON_EXPRESSION])
                    ) {
                        $item[$fieldName] = $item[TaskInterface::CRON_EXPRESSION];
                    } elseif (isset($scheduleTypes[$scheduleType])) {
                        $item[$fieldName] = $scheduleTypes[$scheduleType];
                    }
                }
            }
        }

        return $dataSource;
    }
}
