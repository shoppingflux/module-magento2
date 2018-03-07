<?php

namespace ShoppingFeed\Manager\Console\Command\Feed;

use Magento\Framework\App\Area as AppArea;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Console\Command\Exception as CommandException;
use ShoppingFeed\Manager\Model\Feed\Product as FeedProduct;
use ShoppingFeed\Manager\Model\Feed\Product\Filter as FeedProductFilter;
use ShoppingFeed\Manager\Model\Feed\Product\FilterFactory as FeedProductFilterFactory;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Filter as FeedSectionFilter;
use ShoppingFeed\Manager\Model\Feed\Product\Section\FilterFactory as FeedSectionFilterFactory;
use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractType as SectionType;
use ShoppingFeed\Manager\Model\Feed\Product\Section\TypePoolInterface as SectionTypePoolInterface;
use ShoppingFeed\Manager\Model\Feed\Refresher as FeedRefresher;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\Collection as StoreCollection;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\CollectionFactory as StoreCollectionFactory;
use ShoppingFeed\Manager\Model\Time\Filter as TimeFilter;
use ShoppingFeed\Manager\Model\Time\FilterFactory as TimeFilterFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;


abstract class AbstractCommand extends Command
{
    const ARGUMENT_KEY_REFRESH_STATE = 'refresh_state';

    const OPTION_KEY_STORE_IDS = 'store_id';
    const OPTION_KEY_EXPORT_STATES = 'export_state';
    const OPTION_KEY_SECTION_TYPES = 'section_type';
    const OPTION_KEY_SELECTED_ONLY = 'selected_only';
    const OPTION_KEY_REFRESH_STATES = 'refresh_state';
    const OPTION_VALUE_ALL = 'all';

    const REFRESH_STATE_ADVISED = 'advised';
    const REFRESH_STATE_REQUIRED = 'required';

    const REFRESH_STATE_MAP = [
        self::REFRESH_STATE_ADVISED => FeedRefresher::REFRESH_STATE_ADVISED,
        self::REFRESH_STATE_REQUIRED => FeedRefresher::REFRESH_STATE_REQUIRED,
    ];

    const EXPORT_STATE_EXPORTED = 'exported';
    const EXPORT_STATE_RETAINED = 'retained';
    const EXPORT_STATE_NOT_EXPORTED = 'not_exported';
    const EXPORT_STATE_NEVER_EXPORTED = 'never_exported';

    const EXPORT_STATE_MAP = [
        self::EXPORT_STATE_EXPORTED => FeedProduct::STATE_EXPORTED,
        self::EXPORT_STATE_RETAINED => FeedProduct::STATE_RETAINED,
        self::EXPORT_STATE_NOT_EXPORTED => FeedProduct::STATE_NOT_EXPORTED,
        self::EXPORT_STATE_NEVER_EXPORTED => FeedProduct::STATE_NEVER_EXPORTED,
    ];

    /**
     * @var AppState
     */
    private $appState;

    /**
     * @var StoreCollectionFactory
     */
    private $storeCollectionFactory;

    /**
     * @var SectionTypePoolInterface
     */
    private $sectionTypePool;

    /**
     * @var
     */
    private $timeFilterFactory;

    /**
     * @var FeedProductFilterFactory
     */
    private $feedProductFilterFactory;

    /**
     * @var FeedSectionFilterFactory
     */
    private $feedSectionFilterFactory;

    /**
     * @param AppState $appState
     * @param StoreCollectionFactory $storeCollectionFactory
     * @param SectionTypePoolInterface $sectionTypePool
     * @param TimeFilterFactory $timeFilterFactory
     * @param FeedProductFilterFactory $feedProductFilterFactory
     * @param FeedSectionFilterFactory $feedSectionFilterFactory
     */
    public function __construct(
        AppState $appState,
        StoreCollectionFactory $storeCollectionFactory,
        SectionTypePoolInterface $sectionTypePool,
        TimeFilterFactory $timeFilterFactory,
        FeedProductFilterFactory $feedProductFilterFactory,
        FeedSectionFilterFactory $feedSectionFilterFactory
    ) {
        $this->appState = $appState;

        try {
            $appState->setAreaCode(AppArea::AREA_FRONTEND);
        } catch (\Exception $e) {
            // Area code is most likely already set.
        }

        $this->storeCollectionFactory = $storeCollectionFactory;
        $this->sectionTypePool = $sectionTypePool;
        $this->timeFilterFactory = $timeFilterFactory;
        $this->feedProductFilterFactory = $feedProductFilterFactory;
        $this->feedSectionFilterFactory = $feedSectionFilterFactory;

        parent::__construct();
    }

    protected function configure()
    {
        try {
            $this->appState->setAreaCode(AppArea::AREA_FRONTEND);
        } catch (\Exception $e) {
            // Area code is most likely already set.
        }

        parent::configure();
    }

    /**
     * @return string[]
     */
    public function getSectionTypeCodes()
    {
        return $this->sectionTypePool->getTypeCodes();
    }

    /**
     * @return TimeFilter
     */
    public function createTimeFilter()
    {
        return $this->timeFilterFactory->create();
    }

    /**
     * @return FeedProductFilter
     */
    public function createFeedProductFilter()
    {
        return $this->feedProductFilterFactory->create();
    }

    /**
     * @return FeedSectionFilter
     */
    public function createFeedSectionFilter()
    {
        return $this->feedSectionFilterFactory->create();
    }

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
     * @param bool $withAllOption
     * @return string
     */
    protected function getRefreshStateDescriptionList($withAllOption)
    {
        return $this->getDescriptionValueList(array_keys(self::REFRESH_STATE_MAP), $withAllOption);
    }

    /**
     * @param bool $withAllOption
     * @return string
     */
    protected function getExportStateDescriptionList($withAllOption)
    {
        return $this->getDescriptionValueList(array_keys(self::EXPORT_STATE_MAP), $withAllOption);
    }

    /**
     * @param bool $withAllOption
     * @return string
     */
    protected function getSectionTypesDescriptionList($withAllOption)
    {
        return $this->getDescriptionValueList($this->getSectionTypeCodes(), $withAllOption);
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
     * @param string $description
     * @param string $default
     * @return InputArgument
     */
    protected function getRefreshStateArgument($description, $default = self::REFRESH_STATE_REQUIRED)
    {
        return $this->getArgument(
            self::ARGUMENT_KEY_REFRESH_STATE,
            false,
            false,
            sprintf($description, $this->getRefreshStateDescriptionList(false)),
            $default
        );
    }

    /**
     * @param string $name
     * @param string $shortcut
     * @param bool $isRequired
     * @param bool $isMultiple
     * @param bool $acceptsAllValue
     * @param string $description
     * @param mixed|null $default
     * @return InputOption
     */
    protected function getOption(
        $name,
        $shortcut,
        $isRequired,
        $isMultiple,
        $acceptsAllValue,
        $description,
        $default = null
    ) {
        $mode = ($isRequired && (!$isMultiple || !$acceptsAllValue))
            ? InputOption::VALUE_REQUIRED
            : InputOption::VALUE_OPTIONAL;

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

    // @todo factorize the get*Option methods (getChoiceOption), based on getSectionTypesOptions()

    /**
     * @param string $description
     * @param string|null $name
     * @return InputOption
     */
    protected function getStoreIdsOption($description, $name = null)
    {
        return $this->getOption(
            $name ?? self::OPTION_KEY_STORE_IDS,
            null,
            false,
            true,
            true,
            $description,
            [ self::OPTION_VALUE_ALL ]
        );
    }

    /**
     * @param string $description
     * @param bool $defaultsToAll
     * @param string|null $name
     * @return InputOption
     */
    protected function getExportStatesOption($description, $defaultsToAll = true, $name = null)
    {
        return $this->getOption(
            $name ?? self::OPTION_KEY_EXPORT_STATES,
            null,
            !$defaultsToAll,
            true,
            $defaultsToAll,
            sprintf($description, $this->getExportStateDescriptionList($defaultsToAll)),
            $defaultsToAll ? [ self::OPTION_VALUE_ALL ] : []
        );
    }

    /**
     * @param string $description
     * @param bool $withAllOption
     * @param bool $defaultsToAll
     * @param string|null $name
     * @return InputOption
     */
    protected function getSectionTypesOptions($description, $withAllOption = true, $defaultsToAll = true, $name = null)
    {
        return $this->getOption(
            $name ?? self::OPTION_KEY_SECTION_TYPES,
            null,
            !$defaultsToAll,
            true,
            $withAllOption,
            sprintf($description, $this->getSectionTypesDescriptionList($withAllOption)),
            $defaultsToAll ? [ self::OPTION_VALUE_ALL ] : []
        );
    }

    /**
     * @param string $description
     * @param string|null $name
     * @return InputOption
     */
    protected function getSelectedOnlyOption($description, $name = null)
    {
        return $this->getFlagOption($name ?? self::OPTION_KEY_SELECTED_ONLY, $description);
    }

    /**
     * @param string $description
     * @param bool $defaultsToAll
     * @param string|null $name
     * @return InputOption
     */
    protected function getRefreshStatesOption($description, $defaultsToAll = true, $name = null)
    {
        return $this->getOption(
            $name ?? self::OPTION_KEY_REFRESH_STATES,
            null,
            !$defaultsToAll,
            true,
            $defaultsToAll,
            sprintf($description, $this->getRefreshStateDescriptionList($defaultsToAll)),
            $defaultsToAll ? [ self::OPTION_VALUE_ALL ] : []
        );
    }

    /**
     * @param InputInterface $input
     * @return int
     * @throws CommandException
     */
    protected function getRefreshState(InputInterface $input)
    {
        $refreshState = $input->getArgument(self::ARGUMENT_KEY_REFRESH_STATE);

        if (!isset(self::REFRESH_STATE_MAP[$refreshState])) {
            throw new CommandException('Unknown refresh state: "' . $refreshState . '"');
        }

        return self::REFRESH_STATE_MAP[$refreshState];
    }

    /**
     * @param InputInterface $input
     * @param string|null $optionName
     * @return StoreCollection
     * @throws CommandException
     */
    protected function getStoreCollection(InputInterface $input, $optionName = null)
    {
        $storeCollection = $this->storeCollectionFactory->create();
        $storeIds = (array) $input->getOption($optionName ?? self::OPTION_KEY_STORE_IDS);

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

    /**
     * @param InputInterface $input
     * @param bool $defaultsToAll
     * @param string|null $optionName
     * @return int[]
     * @throws CommandException
     */
    protected function getExportStates(InputInterface $input, $defaultsToAll = true, $optionName = null)
    {
        $exportStates = (array) $input->getOption($optionName ?? self::OPTION_KEY_EXPORT_STATES);
        $productExportStates = [];

        if ($defaultsToAll && in_array(self::OPTION_VALUE_ALL, $exportStates, true)) {
            $exportStates = array_keys(self::EXPORT_STATE_MAP);
        }

        foreach ($exportStates as $exportState) {
            if (isset(self::EXPORT_STATE_MAP[$exportState])) {
                $productExportStates[] = self::EXPORT_STATE_MAP[$exportState];
            } else {
                throw new CommandException('Unknown export state: "' . (string) $exportState . '"');
            }
        }

        return $productExportStates;
    }

    /**
     * @param InputInterface $input
     * @param bool $withAllOption
     * @param null $optionName
     * @return SectionType[]
     * @throws CommandException
     */
    protected function getSectionTypes(InputInterface $input, $withAllOption = true, $optionName = null)
    {
        $typeCodes = (array) $input->getOption($optionName ?? self::OPTION_KEY_SECTION_TYPES);
        $types = [];

        if ($withAllOption && in_array(self::OPTION_VALUE_ALL, $typeCodes, true)) {
            $typeCodes = $this->getSectionTypeCodes();
        }

        foreach ($typeCodes as $typeCode) {
            try {
                $types[] = $this->sectionTypePool->getTypeByCode($typeCode);
            } catch (LocalizedException $e) {
                throw new CommandException('Unknown section type: "' . (string) $typeCode . '"');
            }
        }

        return $types;
    }

    /**
     * @param InputInterface $input
     * @param bool $defaultsToAll
     * @param string|null $optionName
     * @return int[]
     * @throws CommandException
     */
    protected function getSectionTypeIds(InputInterface $input, $defaultsToAll = true, $optionName = null)
    {
        $sectionTypes = $this->getSectionTypes($input, $defaultsToAll, $optionName);
        $typeIds = [];

        foreach ($sectionTypes as $sectionType) {
            $typeIds[] = $sectionType->getId();
        }

        return $typeIds;
    }

    /**
     * @param InputInterface $input
     * @param string|null $optionName
     * @return bool
     */
    protected function getSelectedOnly(InputInterface $input, $optionName = null)
    {
        return (bool) $input->getOption($optionName ?? self::OPTION_KEY_SELECTED_ONLY);
    }

    /**
     * @param InputInterface $input
     * @param bool $defaultsToAll
     * @param string|null $optionName
     * @return int[]
     * @throws CommandException
     */
    protected function getRefreshStates(InputInterface $input, $defaultsToAll = true, $optionName = null)
    {
        $refreshStates = (array) $input->getOption($optionName ?? self::OPTION_KEY_REFRESH_STATES);
        $productRefreshStates = [];

        if ($defaultsToAll && in_array(self::OPTION_VALUE_ALL, $refreshStates, true)) {
            $refreshStates = array_keys(self::REFRESH_STATE_MAP);
        }

        foreach ($refreshStates as $refreshState) {
            if (isset(self::REFRESH_STATE_MAP[$refreshState])) {
                $productRefreshStates[] = self::REFRESH_STATE_MAP[$refreshState];
            } else {
                throw new CommandException('Unknown refresh state: "' . (string) $refreshState . '"');
            }
        }

        return $productRefreshStates;
    }
}
