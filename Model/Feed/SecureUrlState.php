<?php

namespace ShoppingFeed\Manager\Model\Feed;

class SecureUrlState
{
    /**
     * @var bool
     */
    private $isSecureUrlForced = false;

    /**
     * @return bool
     */
    public function isSecureUrlForced()
    {
        return $this->isSecureUrlForced;
    }

    /**
     * @param bool $isSecureUrlForced
     * @param callable $callback
     * @return mixed
     */
    public function runWithSecureUrlForcing($isSecureUrlForced, callable $callback)
    {
        $wasSecureUrlForced = $this->isSecureUrlForced;
        $this->isSecureUrlForced = (bool) $isSecureUrlForced;

        try {
            return $callback();
        } finally {
            $this->isSecureUrlForced = $wasSecureUrlForced;
        }
    }
}
