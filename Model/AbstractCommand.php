<?php

namespace ShoppingFeed\Manager\Model;

use ShoppingFeed\Manager\Model\Command\ConfigInterface;

abstract class AbstractCommand implements CommandInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    public function getConfig()
    {
        return $this->config;
    }
}
