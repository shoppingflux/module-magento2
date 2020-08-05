<?php

namespace ShoppingFeed\Manager\Model\Marketplace\Order\Syncing\Action;

use Magento\Framework\Data\OptionSourceInterface;
use ShoppingFeed\Manager\Model\Sales\Order\SyncerInterface;

class Source implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => SyncerInterface::SYNCING_ACTION_NONE,
                'label' => __('Do nothing'),
            ],
            [
                'value' => SyncerInterface::SYNCING_ACTION_HOLD,
                'label' => __('Block order'),
            ],
            [
                'value' => SyncerInterface::SYNCING_ACTION_CANCEL,
                'label' => __('Cancel order'),
            ],
            [
                'value' => SyncerInterface::SYNCING_ACTION_REFUND,
                'label' => __('Refund order'),
            ],
            [
                'value' => SyncerInterface::SYNCING_ACTION_CANCEL_OR_REFUND,
                'label' => __('Cancel or refund order'),
            ],
        ];
    }
}
