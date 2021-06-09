<?php

namespace ShoppingFeed\Manager\Model\Cron\Group;

use Magento\Cron\Model\Groups\Config\Data as CronGroupConfig;
use Magento\Framework\Data\OptionSourceInterface;
use ShoppingFeed\Manager\Api\Data\Cron\TaskInterface;
use ShoppingFeed\Manager\Model\Source\WithOptionHash;

class Source implements OptionSourceInterface
{
    use WithOptionHash;

    /**
     * @var CronGroupConfig
     */
    private $cronGroupConfig;

    /**
     * @var array|null
     */
    private $optionArray = null;

    /**
     * @param CronGroupConfig $cronGroupConfig
     */
    public function __construct(CronGroupConfig $cronGroupConfig)
    {
        $this->cronGroupConfig = $cronGroupConfig;
    }

    public function toOptionArray()
    {
        if (null === $this->optionArray) {
            $groups = $this->cronGroupConfig->get();
            $this->optionArray = [];

            foreach (array_keys($groups) as $groupId) {
                $this->optionArray[] = [
                    'label' => $groupId,
                    'value' => $groupId,
                ];
            }

            usort(
                $this->optionArray,
                function ($groupA, $groupB) {
                    if (TaskInterface::DEFAULT_CRON_GROUP === $groupA['value']) {
                        return -1;
                    } elseif (TaskInterface::DEFAULT_CRON_GROUP === $groupB['value']) {
                        return 1;
                    }

                    return strnatcmp($groupA['value'], $groupB['value']);
                }
            );
        }

        return $this->optionArray;
    }
}
