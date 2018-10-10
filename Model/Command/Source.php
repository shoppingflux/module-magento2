<?php

namespace ShoppingFeed\Manager\Model\Command;

use Magento\Framework\Data\OptionSourceInterface;
use ShoppingFeed\Manager\Model\CommandPoolInterface;

class Source implements OptionSourceInterface
{
    /**
     * @var CommandPoolInterface
     */
    private $commandPool;

    /**
     * @var array|null
     */
    private $optionArray = null;

    /**
     * @param CommandPoolInterface $commandPool
     */
    public function __construct(CommandPoolInterface $commandPool)
    {
        $this->commandPool = $commandPool;
    }

    public function toOptionArray()
    {
        if (null === $this->optionArray) {
            $this->optionArray = [];

            foreach ($this->commandPool->getGroups() as $commandGroup) {
                $commandOptions = [];

                foreach ($commandGroup->getCommands() as $commandCode => $command) {
                    $commandOptions[] = [
                        'value' => $commandCode,
                        'label' => $command->getLabel(),
                    ];
                }

                $this->optionArray[] = [
                    'value' => $commandOptions,
                    'label' => $commandGroup->getLabel(),
                ];
            }
        }

        return $this->optionArray;
    }
}
