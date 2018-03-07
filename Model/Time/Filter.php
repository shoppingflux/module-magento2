<?php

namespace ShoppingFeed\Manager\Model\Time;


class Filter
{
    const MODE_BEFORE = 'before';
    const MODE_AFTER = 'after';

    /**
     * @var string
     */
    private $mode = self::MODE_BEFORE;

    /**
     * @var int|null
     */
    private $seconds = null;

    /**
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @return int|null
     */
    public function getSeconds()
    {
        return $this->seconds;
    }

    /**
     * @param string $mode
     * @return $this
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * @param int $seconds
     * @return $this
     */
    public function setSeconds($seconds)
    {
        $this->seconds = abs((int) $seconds);
        return $this;
    }

    /**
     * @param int $minutes
     * @return $this
     */
    public function setMinutes($minutes)
    {
        return $this->setSeconds((int) $minutes * 60);
    }

    /**
     * @param int $hours
     * @return $this
     */
    public function setHours($hours)
    {
        return $this->setSeconds((int) $hours * 3600);
    }

    /**
     * @param int $days
     * @return $this
     */
    public function setDays($days)
    {
        return $this->setSeconds((int) $days * 86400);
    }
}
