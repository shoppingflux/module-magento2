<?php

namespace ShoppingFeed\Manager\Console;

use Magento\Framework\App\Area as AppArea;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Config\ScopeInterface as ConfigScopeInterface;
use ShoppingFeed\Manager\Console\Command\Exception as CommandException;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\Collection as StoreCollection;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\CollectionFactory as StoreCollectionFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command
{
    const OPTION_KEY_ACCOUNT_IDS = 'account_id';
    const OPTION_KEY_STORE_IDS = 'store_id';
    const OPTION_VALUE_ALL = 'all';

    /**
     * @var AppState
     */
    private $appState;

    /**
     * @var ConfigScopeInterface
     */
    private $configScope;

    /**
     * @var StoreCollectionFactory
     */
    private $storeCollectionFactory;

    /**
     * @param AppState $appState
     * @param ConfigScopeInterface $configScope
     * @param StoreCollectionFactory $storeCollectionFactory
     */
    public function __construct(
        AppState $appState,
        ConfigScopeInterface $configScope,
        StoreCollectionFactory $storeCollectionFactory
    ) {
        $this->appState = $appState;
        $this->configScope = $configScope;
        $this->storeCollectionFactory = $storeCollectionFactory;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->appState->emulateAreaCode(
            AppArea::AREA_FRONTEND,
            function () use ($input, $output) {
                $originalScope = $this->configScope->getCurrentScope();
                $this->configScope->setCurrentScope(AppArea::AREA_FRONTEND);
                $result = $this->executeActions($input, $output);
                $this->configScope->setCurrentScope($originalScope);

                return $result;
            }
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    abstract protected function executeActions(InputInterface $input, OutputInterface $output);

    /**
     * @return InputArgument[]
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * @return InputOption[]
     */
    protected function getOptions()
    {
        return [];
    }

    /**
     * @param string $name
     * @param bool $isRequired
     * @param bool $isMultiple
     * @param string $description
     * @param mixed|null $default
     * @return InputArgument
     */
    protected function getArgument($name, $isRequired, $isMultiple, $description, $default = null)
    {
        $mode = $isRequired ? InputArgument::REQUIRED : InputArgument::OPTIONAL;

        if ($isMultiple) {
            $mode |= InputArgument::IS_ARRAY;
        }

        return new InputArgument(
            $name,
            $mode,
            $description,
            $isMultiple ? (array) $default : $default
        );
    }

    /**
     * @param string $name
     * @param string $shortcut
     * @param bool $isRequired
     * @param bool $isMultiple
     * @param string $description
     * @param mixed|null $default
     * @return InputOption
     */
    protected function getOption(
        $name,
        $shortcut,
        $isRequired,
        $isMultiple,
        $description,
        $default = null
    ) {
        $mode = $isRequired ? InputOption::VALUE_REQUIRED : InputOption::VALUE_OPTIONAL;

        if ($isMultiple) {
            $mode |= InputOption::VALUE_IS_ARRAY;
        }

        return new InputOption(
            $name,
            $shortcut,
            $mode,
            $description,
            $isMultiple ? (array) $default : $default
        );
    }

    /**
     * @param string $name
     * @param string $description
     * @return InputOption
     */
    protected function getFlagOption($name, $description)
    {
        return new InputOption(
            $name,
            null,
            InputOption::VALUE_NONE,
            $description
        );
    }

    /**
     * @param InputInterface $input
     * @param string $name
     * @return bool
     */
    protected function getFlagOptionValue(InputInterface $input, $name)
    {
        return (bool) $input->getOption($name);
    }

    /**
     * @param string[] $values
     * @param bool $withAllOption
     * @return string
     */
    protected function getDescriptionValueList(array $values, $withAllOption)
    {
        $valueList = [];

        foreach ($values as $value) {
            $valueList[] = '"' . $value . '"';
        }

        if ($withAllOption) {
            $valueList[] = '"' . self::OPTION_VALUE_ALL . '"';
        }

        return implode('|', $valueList);
    }

    /**
     * @param string $name
     * @param string $description
     * @param bool $withAllOption
     * @param bool $defaultsToAll
     * @return InputOption
     */
    protected function getChoiceOption($name, $description, $withAllOption = true, $defaultsToAll = true)
    {
        return $this->getOption(
            $name,
            null,
            !$withAllOption || $defaultsToAll,
            true,
            $description,
            $defaultsToAll ? [ self::OPTION_VALUE_ALL ] : []
        );
    }

    /**
     * @param InputInterface $input
     * @param string $name
     * @param array $allValues
     * @return array
     */
    protected function getChoiceOptionValue(InputInterface $input, $name, $allValues = [])
    {
        $values = (array) $input->getOption($name);

        if (in_array(self::OPTION_VALUE_ALL, $values, true)) {
            $values = $allValues;
        }

        return $values;
    }

    /**
     * @param string $description
     * @param bool $defaultsToAll
     * @param string|null $name
     * @return InputOption
     */
    protected function getAccountsOption($description, $defaultsToAll = true, $name = null)
    {
        return $this->getChoiceOption(
            $name ?? self::OPTION_KEY_ACCOUNT_IDS,
            $description,
            true,
            $defaultsToAll
        );
    }

    /**
     * @param string $description
     * @param bool $defaultsToAll
     * @param string|null $name
     * @return InputOption
     */
    protected function getStoresOption($description, $defaultsToAll = true, $name = null)
    {
        return $this->getChoiceOption(
            $name ?? self::OPTION_KEY_STORE_IDS,
            '[obsolete] ' . $description . ' (please use "' . self::OPTION_KEY_ACCOUNT_IDS . '" instead)',
            true,
            $defaultsToAll
        );
    }

    /**
     * @param InputInterface $input
     * @param string|null $name
     * @return StoreCollection
     * @throws CommandException
     */
    protected function getStoresOptionCollection(InputInterface $input, $name = null)
    {
        if (null !== $name) {
            $storeIds = (array) $input->getOption($name);
        } else {
            if ($input->hasParameterOption('--' . self::OPTION_KEY_ACCOUNT_IDS)) {
                if ($input->hasParameterOption('--' . self::OPTION_KEY_STORE_IDS)) {
                    throw new CommandException(
                        'Options "'
                        . self::OPTION_KEY_ACCOUNT_IDS
                        . '" and "'
                        . self::OPTION_KEY_STORE_IDS
                        . '" should not be used together.'
                    );
                }

                $storeIds = (array) $input->getOption(self::OPTION_KEY_ACCOUNT_IDS);
            } else {
                $storeIds = (array) $input->getOption(self::OPTION_KEY_STORE_IDS);
            }
        }

        $storeCollection = $this->storeCollectionFactory->create();

        if (!in_array(self::OPTION_VALUE_ALL, $storeIds, true)) {
            if (!empty($storeIds)) {
                $storeCollection->addIdFilter($storeIds);
            } else {
                $storeCollection->addIdFilter(-1);
            }
        }

        $storeCollection->load();
        $missingStoreIds = array_diff($storeIds, [ self::OPTION_VALUE_ALL ]);

        foreach ($storeCollection as $store) {
            $storeKey = array_search($store->getId(), $missingStoreIds);

            if (false !== $storeKey) {
                unset($missingStoreIds[$storeKey]);
            }
        }

        if (!empty($missingStoreIds)) {
            sort($missingStoreIds);
            throw new CommandException('Unknown store ID(s): ' . implode(', ', $missingStoreIds));
        }

        return $storeCollection;
    }
}
