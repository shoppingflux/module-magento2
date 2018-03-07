<?php

namespace ShoppingFeed\Manager\Model\ShoppingFeed\Api\Exception;

use ShoppingFeed\Manager\Model\ShoppingFeed\Api\AbstractException;


class BadRequest extends AbstractException
{
    protected function getBasePhrase()
    {
        return __('Invalid request');
    }
}
