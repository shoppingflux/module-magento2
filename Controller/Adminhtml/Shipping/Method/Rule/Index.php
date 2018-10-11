<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Shipping\Method\Rule;

use ShoppingFeed\Manager\Controller\Adminhtml\Shipping\Method\RuleAction;

class Index extends RuleAction
{
    public function execute()
    {
        return $this->initPage();
    }
}
