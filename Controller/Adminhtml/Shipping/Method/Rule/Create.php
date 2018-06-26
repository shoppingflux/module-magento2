<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Shipping\Method\Rule;

use ShoppingFeed\Manager\Controller\Adminhtml\Shipping\Method\RuleAction;


class Create extends RuleAction
{
    public function execute()
    {
        $this->_forward('edit');
    }
}
