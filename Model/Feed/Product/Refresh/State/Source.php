<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Refresh\State;

use Magento\Framework\Data\OptionSourceInterface;
use ShoppingFeed\Manager\Api\Data\Feed\ProductInterface as FeedProductInterface;
use ShoppingFeed\Manager\Model\Source\WithOptionHash;

class Source implements OptionSourceInterface
{
    use WithOptionHash;

    public function toOptionArray()
    {
        return [
            [
                'value' => FeedProductInterface::REFRESH_STATE_UP_TO_DATE,
                'label' => (string) __('Up To Date'),
            ],
            [
                'value' => FeedProductInterface::REFRESH_STATE_ADVISED,
                'label' => (string) __('Update Advised'),
            ],
            [
                'value' => FeedProductInterface::REFRESH_STATE_REQUIRED,
                'label' => (string) __('Update Required'),
            ],
        ];
    }
}
