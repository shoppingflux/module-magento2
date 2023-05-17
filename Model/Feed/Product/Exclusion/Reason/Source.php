<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Exclusion\Reason;

use Magento\Framework\Data\OptionSourceInterface;
use ShoppingFeed\Manager\Api\Data\Feed\ProductInterface;
use ShoppingFeed\Manager\Model\Source\WithOptionHash;

class Source implements OptionSourceInterface
{
    use WithOptionHash;

    public function toOptionArray()
    {
        return [
            [
                'value' => ProductInterface::EXCLUSION_REASON_UNHANDLED_PRODUCT_TYPE,
                'label' => __('Unhandled Product Type'),
            ],
            [
                'value' => ProductInterface::EXCLUSION_REASON_FILTERED_PRODUCT_TYPE,
                'label' => __('Filtered Product Type'),
            ],
            [
                'value' => ProductInterface::EXCLUSION_REASON_NOT_IN_WEBSITE,
                'label' => __('Not in Website'),
            ],
            [
                'value' => ProductInterface::EXCLUSION_REASON_NOT_SALABLE,
                'label' => __('Not Salable'),
            ],
            [
                'value' => ProductInterface::EXCLUSION_REASON_OUT_OF_STOCK,
                'label' => __('Out of Stock'),
            ],
            [
                'value' => ProductInterface::EXCLUSION_REASON_FILTERED_VISIBILITY,
                'label' => __('Filtered Visibility'),
            ],
            [
                'value' => ProductInterface::EXCLUSION_REASON_UNSELECTED_PRODUCT,
                'label' => __('Unselected Product'),
            ],
            [
                'value' => ProductInterface::EXCLUSION_REASON_DISABLED,
                'label' => __('Disabled Product'),
            ],
        ];
    }
}
