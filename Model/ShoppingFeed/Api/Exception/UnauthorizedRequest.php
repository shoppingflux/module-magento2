<?php

namespace ShoppingFeed\Manager\Model\ShoppingFeed\Api\Exception;

use ShoppingFeed\Manager\Model\ShoppingFeed\Api\AbstractException;


class UnauthorizedRequest extends AbstractException
{
    protected function getBasePhrase()
    {
        return __('Unauthorized request');
    }
}
