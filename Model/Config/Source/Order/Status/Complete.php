<?php

namespace ShoppingFeed\Manager\Model\Config\Source\Order\Status;

use Magento\Sales\Model\Config\Source\Order\Status as StatusSource;
use Magento\Sales\Model\Order;

class Complete extends StatusSource
{
    protected $_stateStatuses = Order::STATE_COMPLETE;
}
