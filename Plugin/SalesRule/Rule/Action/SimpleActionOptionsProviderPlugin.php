<?php

namespace ShoppingFeed\Manager\Plugin\SalesRule\Rule\Action;

use Magento\SalesRule\Model\Rule\Action\SimpleActionOptionsProvider;
use ShoppingFeed\Manager\Model\SalesRule\Rule\Action\Discount\Marketplace\CartFixed;

class SimpleActionOptionsProviderPlugin
{
    public function afterToOptionArray(SimpleActionOptionsProvider $subject, $result)
    {
        if (is_array($result)) {
            $result[] = [
                'label' => __('Shopping Feed - Marketplace Cart Discount'),
                'value' => CartFixed::ACTION_CODE,
            ];
        }

        return $result;
    }
}