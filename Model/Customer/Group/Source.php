<?php

namespace ShoppingFeed\Manager\Model\Customer\Group;

use Magento\Customer\Api\Data\GroupInterface as CustomerGroupInterface;
use Magento\Customer\Model\Customer\Source\Group as FullSource;
use Magento\Framework\Data\OptionSourceInterface;

class Source implements OptionSourceInterface
{
    /** @see CustomerGroupInterface::CUST_GROUP_ALL */
    const CUSTOMER_GROUP_ID_NOT_LOGGED_IN = 32001;

    /**
     * @var FullSource
     */
    private $fullSource;

    /**
     * @var bool
     */
    private $withAll;

    /**
     * @var bool
     */
    private $withNotLoggedIn;

    /**
     * @param FullSource $baseSource
     * @param bool $withAll
     * @param bool $withNotLoggedIn
     */
    public function __construct(FullSource $baseSource, $withAll = false, $withNotLoggedIn = true)
    {
        $this->fullSource = $baseSource;
        $this->withAll = (bool) $withAll;
        $this->withNotLoggedIn = (bool) $withNotLoggedIn;
    }

    /**
     * @param int $value
     * @return int
     */
    public function getGroupId($value)
    {
        $groupId = (int) $value;

        return (static::CUSTOMER_GROUP_ID_NOT_LOGGED_IN !== $groupId)
            ? $groupId
            : CustomerGroupInterface::NOT_LOGGED_IN_ID;
    }

    public function toOptionArray()
    {
        $options = [];
        $allCustomerGroups = $this->fullSource->toOptionArray();

        foreach ($allCustomerGroups as $customerGroup) {
            $customerGroupId = (int) $customerGroup['value'];

            if (!$this->withAll && (CustomerGroupInterface::CUST_GROUP_ALL === $customerGroupId)) {
                continue;
            }

            if (CustomerGroupInterface::NOT_LOGGED_IN_ID === $customerGroupId) {
                if (!$this->withNotLoggedIn) {
                    continue;
                }

                // Use a positive ID because a value of 0 is not always correctly handled.
                $customerGroupId = static::CUSTOMER_GROUP_ID_NOT_LOGGED_IN;
            }

            $options[] = [
                'value' => $customerGroupId,
                'label' => trim($customerGroup['label'] ?? ''),
            ];
        }

        return $options;
    }
}
