<?php

namespace ShoppingFeed\Manager\Model\Ui\Payment;

use Magento\Checkout\Model\ConfigProviderInterface;


class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'sfm_shopping_feed_order';

    public function getConfig()
    {
        return [];
    }
}
