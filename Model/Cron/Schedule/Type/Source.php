<?php

namespace ShoppingFeed\Manager\Model\Cron\Schedule\Type;

use Magento\Framework\Data\OptionSourceInterface;
use ShoppingFeed\Manager\Api\Data\Cron\TaskInterface;
use ShoppingFeed\Manager\Model\Source\WithOptionHash;

class Source implements OptionSourceInterface
{
    use WithOptionHash;

    public function toOptionArray()
    {
        return [
            [
                'value' => TaskInterface::SCHEDULE_TYPE_CUSTOM,
                'label' => __('Custom'),
            ],
            [
                'value' => TaskInterface::SCHEDULE_TYPE_NEVER,
                'label' => __('Never (manual run only)'),
            ],
            [
                'value' => TaskInterface::SCHEDULE_TYPE_EVERY_MINUTE,
                'label' => __('Every minute'),
            ],
            [
                'value' => TaskInterface::SCHEDULE_TYPE_EVERY_5_MINUTES,
                'label' => __('Every 5 minutes'),
            ],
            [
                'value' => TaskInterface::SCHEDULE_TYPE_EVERY_15_MINUTES,
                'label' => __('Every 15 minutes'),
            ],
            [
                'value' => TaskInterface::SCHEDULE_TYPE_EVERY_30_MINUTES,
                'label' => __('Every 30 minutes'),
            ],
            [
                'value' => TaskInterface::SCHEDULE_TYPE_EVERY_HOUR,
                'label' => __('Every hour'),
            ],
            [
                'value' => TaskInterface::SCHEDULE_TYPE_EVERY_DAY_AT_4AM,
                'label' => __('Every day at 4:00am'),
            ],
            [
                'value' => TaskInterface::SCHEDULE_TYPE_EVERY_MONDAY_AT_4AM,
                'label' => __('Every Monday at 4:00am'),
            ],
            [
                'value' => TaskInterface::SCHEDULE_TYPE_EVERY_MONTH_FIRST_DAY_AT_4AM,
                'label' => __('Every first day of the month at 4:00am'),
            ],
        ];
    }
}
