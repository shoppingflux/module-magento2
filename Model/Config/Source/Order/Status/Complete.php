<?php

namespace ShoppingFeed\Manager\Model\Config\Source\Order\Status;

use Magento\Framework\App\Area as AppArea;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State as AppState;
use Magento\Sales\Model\Config\Source\Order\Status as StatusSource;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Config as OrderConfig;

class Complete extends AbstractSource
{
    protected $_stateStatuses = Order::STATE_COMPLETE;
}
