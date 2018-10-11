<?php

namespace ShoppingFeed\Manager\Console\Command\Feed;

use Magento\Framework\App\State as AppState;
use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Api\Data\Feed\ProductInterface as FeedProductInterface;
use ShoppingFeed\Manager\Console\AbstractCommand as BaseCommand;
use ShoppingFeed\Manager\Console\Command\Exception as CommandException;
use ShoppingFeed\Manager\Model\Feed\ProductFilter as FeedProductFilter;
use ShoppingFeed\Manager\Model\Feed\ProductFilterFactory as FeedProductFilterFactory;
use ShoppingFeed\Manager\Model\Feed\Product\SectionFilter as FeedSectionFilter;
use ShoppingFeed\Manager\Model\Feed\Product\SectionFilterFactory as FeedSectionFilterFactory;
use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractType as SectionType;
use ShoppingFeed\Manager\Model\Feed\Product\Section\TypePoolInterface as SectionTypePoolInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\CollectionFactory as StoreCollectionFactory;
use ShoppingFeed\Manager\Model\TimeFilter;
use ShoppingFeed\Manager\Model\TimeFilterFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

abstract class AbstractCommand extends BaseCommand
{
    const ARGUMENT_KEY_REFRESH_STATE = 'refresh_state';

    const OPTION_KEY_EXPORT_STATES = 'export_state';
    const OPTION_KEY_SECTION_TYPES = 'section_type';
    const OPTION_KEY_SELECTED_ONLY = 'selected_only';
    const OPTION_KEY_REFRESH_STATES = 'refresh_state';

    const REFRESH_STATE_ADVISED = 'advised';
    const REFRESH_STATE_REQUIRED = 'required';

    const REFRESH_STATE_MAP = [
        self::REFRESH_STATE_ADVISED => FeedProductInterface::REFRESH_STATE_ADVISED,
        self::REFRESH_STATE_REQUIRED => FeedProductInterface::REFRESH_STATE_REQUIRED,
    ];

    const EXPORT_STATE_EXPORTED = 'exported';
    const EXPORT_STATE_RETAINED = 'retained';
    const EXPORT_STATE_NOT_EXPORTED = 'not_exported';
    const EXPORT_STATE_NEVER_EXPORTED = 'never_exported';

    const EXPORT_STATE_MAP = [
        self::EXPORT_STATE_EXPORTED => FeedProductInterface::STATE_EXPORTED,
        self::EXPORT_STATE_RETAINED => FeedProductInterface::STATE_RETAINED,
        self::EXPORT_STATE_NOT_EXPORTED => FeedProductInterface::STATE_NOT_EXPORTED,
        self::EXPORT_STATE_NEVER_EXPORTED => FeedProductInterface::STATE_NEVER_EXPORTED,
    ];

    /**
     * @var SectionTypePoolInterface
     */
    private $sectionTypePool;

    /**
     * @var TimeFilterFactory
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
        $this->sectionTypePool = $sectionTypePool;
        $this->timeFilterFactory = $timeFilterFactory;
        $this->feedProductFilterFactory = $feedProductFilterFactory;
        $this->feedSectionFilterFactory = $feedSectionFilterFactory;
        parent::__construct($appState, $storeCollectionFactory);
    }

    /**
     * @return int[]
     */
    public function getSectionTypeIds()
    {
        return $this->sectionTypePool->getTypeIds();
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
     * @param string $description
     * @param bool $defaultsToAll
     * @param string|null $name
     * @return InputOption
     */
    protected function getExportStatesOption($description, $defaultsToAll = true, $name = null)
    {
        return $this->getChoiceOption(
            $name ?? self::OPTION_KEY_EXPORT_STATES,
            sprintf($description, $this->getExportStateDescriptionList(true)),
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
    protected function getSectionTypesOption($description, $defaultsToAll = true, $name = null)
    {
        return $this->getChoiceOption(
            $name ?? self::OPTION_KEY_SECTION_TYPES,
            sprintf($description, $this->getSectionTypesDescriptionList(true)),
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
    protected function getRefreshStatesOption($description, $defaultsToAll = true, $name = null)
    {
        return $this->getChoiceOption(
            $name ?? self::OPTION_KEY_REFRESH_STATES,
            sprintf($description, $this->getRefreshStateDescriptionList(true)),
            true,
            $defaultsToAll
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
     * @param InputInterface $input
     * @return int
     * @throws CommandException
     */
    protected function getRefreshStateArgumentValue(InputInterface $input)
    {
        $refreshState = $input->getArgument(self::ARGUMENT_KEY_REFRESH_STATE);

        if (!isset(self::REFRESH_STATE_MAP[$refreshState])) {
            throw new CommandException('Unknown refresh state: "' . $refreshState . '"');
        }

        return self::REFRESH_STATE_MAP[$refreshState];
    }

    /**
     * @param InputInterface $input
     * @param string|null $name
     * @return int[]
     * @throws CommandException
     */
    protected function getExportStatesOptionValue(InputInterface $input, $name = null)
    {
        $exportStates = $this->getChoiceOptionValue(
            $input,
            $name ?? self::OPTION_KEY_EXPORT_STATES,
            array_keys(self::EXPORT_STATE_MAP)
        );

        $productExportStates = [];

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
     * @param string|null $name
     * @return SectionType[]
     * @throws CommandException
     */
    protected function getSectionTypesOptionValue(InputInterface $input, $name = null)
    {
        $typeCodes = $this->getChoiceOptionValue(
            $input,
            $name ?? self::OPTION_KEY_SECTION_TYPES,
            $this->getSectionTypeCodes()
        );

        $types = [];

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
     * @param string|null $name
     * @return int[]
     * @throws CommandException
     */
    protected function getSectionTypesOptionIds(InputInterface $input, $name = null)
    {
        $sectionTypes = $this->getSectionTypesOptionValue($input, $name);
        $typeIds = [];

        foreach ($sectionTypes as $sectionType) {
            $typeIds[] = $sectionType->getId();
        }

        return $typeIds;
    }

    /**
     * @param InputInterface $input
     * @param string|null $name
     * @return bool
     */
    protected function getSelectedOnlyOptionValue(InputInterface $input, $name = null)
    {
        return (bool) $input->getOption($name ?? self::OPTION_KEY_SELECTED_ONLY);
    }

    /**
     * @param InputInterface $input
     * @param string|null $name
     * @return int[]
     * @throws CommandException
     */
    protected function getRefreshStatesOptionValue(InputInterface $input, $name = null)
    {
        $refreshStates = $this->getChoiceOptionValue(
            $input,
            $name ?? self::OPTION_KEY_REFRESH_STATES,
            array_keys(self::REFRESH_STATE_MAP)
        );

        $productRefreshStates = [];

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
