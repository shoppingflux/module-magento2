<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Export\State;

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
                'value' => FeedProductInterface::STATE_EXPORTED,
                'label' => (string) __('Exported'),
            ],
            [
                'value' => FeedProductInterface::STATE_RETAINED,
                'label' => (string) __('Retained'),
            ],
            [
                'value' => FeedProductInterface::STATE_NOT_EXPORTED,
                'label' => (string) __('Not Exported'),
            ],
            [
                'value' => FeedProductInterface::STATE_NEVER_EXPORTED,
                'label' => (string) __('Never Exported'),
            ],
        ];
    }
}
