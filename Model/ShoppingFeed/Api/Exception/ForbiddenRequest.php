<?php

namespace ShoppingFeed\Manager\Model\ShoppingFeed\Api\Exception;

use ShoppingFeed\Manager\Model\ShoppingFeed\Api\AbstractException;


class ForbiddenRequest extends AbstractException
{
    protected function getBasePhrase()
    {
        return __('Forbidden request');
    }
}
