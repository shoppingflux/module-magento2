<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Shipping\Method\Rule;

use Magento\Rule\Model\ResourceModel\Rule\Collection\AbstractCollection;
use ShoppingFeed\Manager\Model\Shipping\Method\Rule;
use ShoppingFeed\Manager\Model\ResourceModel\Shipping\Method\Rule as RuleResource;


class Collection extends AbstractCollection
{
    protected $_idFieldName = 'rule_id';

    protected function _construct()
    {
        $this->_init(Rule::class, RuleResource::class);
    }
}
