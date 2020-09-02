<?php

namespace ShoppingFeed\Manager\Model;

abstract class AbstractFilter
{
    /**
     * @return bool
     */
    abstract public function isEmpty();
}
