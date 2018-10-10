<?php

namespace ShoppingFeed\Manager\Model;

class CommandGroup
{
    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $label;

    /**
     * @var CommandInterface[]
     */
    private $commands;

    /**
     * @param string $code
     * @param string $label
     * @param CommandInterface[] $commands
     */
    public function __construct($code, $label, array $commands = [])
    {
        $this->code = $code;
        $this->label = $label;
        $this->commands = $commands;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return CommandInterface[]
     */
    public function getCommands()
    {
        return $this->commands;
    }
}
