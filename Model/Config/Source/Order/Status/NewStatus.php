<?php

namespace ShoppingFeed\Manager\Model\Config\Source\Order\Status;

use Magento\Sales\Model\Order;

class NewStatus extends AbstractSource
{
    protected $_stateStatuses = Order::STATE_NEW;
}