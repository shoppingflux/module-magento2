<?php

namespace ShoppingFeed\Manager\Model;

use Magento\Framework\Exception\LocalizedException;

interface CommandPoolInterface
{
    /**
     * @return CommandGroup[]
     */
    public function getGroups();

    /**
     * @param string $code
     * @return CommandGroup
     * @throws LocalizedException
     */
    public function getGroupByCode($code);

    /**
     * @return string[]
     */
    public function getCommandCodes();

    /**
     * @return CommandInterface[]
     */
    public function getCommands();

    /**
     * @param string $code
     * @return CommandInterface
     * @throws LocalizedException
     */
    public function getCommandByCode($code);
}
