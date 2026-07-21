<?php

namespace ShoppingFeed\Manager\Model\Marketplace\Order\Ticket\Status;

use Magento\Framework\Data\OptionSourceInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\TicketInterface;

class Source implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => TicketInterface::STATUS_PENDING,
                'label' => __('Pending'),
            ],
            [
                'value' => TicketInterface::STATUS_HANDLED,
                'label' => __('Handled'),
            ],
            [
                'value' => TicketInterface::STATUS_FAILED,
                'label' => __('Failed'),
            ],
        ];
    }
}
