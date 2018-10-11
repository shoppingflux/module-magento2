<?php

namespace ShoppingFeed\Manager\Model;

use Magento\Framework\Exception\LocalizedException;

class CommandPool implements CommandPoolInterface
{
    /**
     * @var CommandGroupFactory
     */
    private $groupFactory;

    /**
     * @var CommandGroup[]
     */
    private $groups = [];

    /**
     * @var CommandInterface[]
     */
    private $commands = [];

    /**
     * @param CommandGroupFactory $groupFactory
     * @param array $groups
     * @param CommandInterface[] $commands
     * @throws LocalizedException
     */
    public function __construct(CommandGroupFactory $groupFactory, array $groups, array $commands = [])
    {
        $groupCommands = [];

        foreach ($commands as $groupCode => $subCommands) {
            if (isset($groups[$groupCode])) {
                foreach ($subCommands as $code => $command) {
                    if (!$command instanceof CommandInterface) {
                        throw new LocalizedException(
                            __(
                                'Command "%1" must be of type: ShoppingFeed\Manager\Model\CommandInterface.',
                                $code
                            )
                        );
                    }

                    $commandCode = $groupCode . '/' . $code;
                    $this->commands[$commandCode] = $command;
                    $groupCommands[$groupCode][$commandCode] = $command;
                }
            } else {
                throw new LocalizedException(__('Command group for code "%1" does not exist.', $groupCode));
            }
        }

        foreach ($groups as $code => $label) {
            $this->groups[$code] = $groupFactory->create(
                [
                    'code' => $code,
                    'label' => $label,
                    'commands' => $groupCommands[$code] ?? [],
                ]
            );
        }
    }

    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @param string $code
     * @return CommandGroup
     * @throws LocalizedException
     */
    public function getGroupByCode($code)
    {
        if (isset($this->groups[$code])) {
            return $this->groups[$code];
        }

        throw new LocalizedException(__('Command group for code "%1" does not exist.', $code));
    }

    public function getCommandCodes()
    {
        return array_keys($this->commands);
    }

    public function getCommands()
    {
        return $this->commands;
    }

    /**
     * @param string $code
     * @return CommandInterface
     * @throws LocalizedException
     */
    public function getCommandByCode($code)
    {
        if (isset($this->commands[$code])) {
            return $this->commands[$code];
        }

        throw new LocalizedException(__('Command for code "%1" does not exist.', $code));
    }
}
