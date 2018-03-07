<?php

namespace ShoppingFeed\Manager\Ui;

use Magento\Framework\Api\Filter as ApiFilter;
use Magento\Ui\DataProvider\AbstractDataProvider;


class EmptyDataProvider extends AbstractDataProvider
{
    public function addFilter(ApiFilter $filter)
    {
    }

    public function getData()
    {
        return [];
    }
}
