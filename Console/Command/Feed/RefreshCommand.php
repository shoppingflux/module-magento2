<?php

namespace ShoppingFeed\Manager\Console\Command\Feed;

use Magento\Framework\App\State as AppState;
use Magento\Framework\Console\Cli;
use ShoppingFeed\Manager\Model\Feed\ProductFilterFactory as FeedProductFilterFactory;
use ShoppingFeed\Manager\Model\Feed\Product\SectionFilterFactory as FeedSectionFilterFactory;
use ShoppingFeed\Manager\Model\Feed\Product\Section\TypePoolInterface as SectionTypePoolInterface;
use ShoppingFeed\Manager\Model\Feed\Refresher as FeedRefresher;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\CollectionFactory as StoreCollectionFactory;
use ShoppingFeed\Manager\Model\TimeFilterFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RefreshCommand extends AbstractCommand
{
    const OPTION_KEY_REFRESH_EXPORT_STATE = 'refresh_export_state';
    const OPTION_KEY_EXPORT_STATE_EXPORT_STATES = 'export_state_export_state';
    const OPTION_KEY_EXPORT_STATE_SELECTED_ONLY = 'export_state_selected_only';
    const OPTION_KEY_EXPORT_STATE_REFRESH_STATES = 'export_state_refresh_state';
    const OPTION_KEY_REFRESH_SECTION_TYPES = 'refresh_section_type';
    const BASE_OPTION_KEY_SECTION_TYPE_EXPORT_STATES = '%s_export_state';
    const BASE_OPTION_KEY_SECTION_TYPE_SELECTED_ONLY = '%s_selected_only';
    const BASE_OPTION_KEY_SECTION_TYPE_REFRESH_STATES = '%s_refresh_state';

    /**
     * @var FeedRefresher
     */
    private $feedRefresher;

    /**
     * @param AppState $appState
     * @param StoreCollectionFactory $storeCollectionFactory
     * @param SectionTypePoolInterface $sectionTypePool
     * @param TimeFilterFactory $timeFilterFactory
     * @param FeedProductFilterFactory $feedProductFilterFactory
     * @param FeedSectionFilterFactory $feedSectionFilterFactory
     * @param FeedRefresher $feedRefresher
     */
    public function __construct(
        AppState $appState,
        StoreCollectionFactory $storeCollectionFactory,
        SectionTypePoolInterface $sectionTypePool,
        TimeFilterFactory $timeFilterFactory,
        FeedProductFilterFactory $feedProductFilterFactory,
        FeedSectionFilterFactory $feedSectionFilterFactory,
        FeedRefresher $feedRefresher
    ) {
        parent::__construct(
            $appState,
            $storeCollectionFactory,
            $sectionTypePool,
            $timeFilterFactory,
            $feedProductFilterFactory,
            $feedSectionFilterFactory
        );

        $this->feedRefresher = $feedRefresher;
    }

    protected function configure()
    {
        $this->setName('shoppingfeed:feed:refresh');
        $this->setDescription('Refreshes feed product data for one or more stores');

        $exportStateOptions = [
            $this->getFlagOption(
                self::OPTION_KEY_REFRESH_EXPORT_STATE,
                'Refresh product export states'
            ),

            $this->getExportStatesOption(
                'Only refresh export states for products with those current export states (%s)',
                false,
                self::OPTION_KEY_EXPORT_STATE_EXPORT_STATES
            ),

            $this->getSelectedOnlyOption(
                'Only refresh export states for selected products',
                self::OPTION_KEY_EXPORT_STATE_SELECTED_ONLY
            ),

            $this->getRefreshStatesOption(
                'Only refresh export states with those refresh states (%s)',
                false,
                self::OPTION_KEY_EXPORT_STATE_REFRESH_STATES
            ),
        ];

        $sectionOptions = [
            $this->getSectionTypesOption(
                'Refresh data for those section types (%s)',
                false,
                self::OPTION_KEY_REFRESH_SECTION_TYPES
            ),
        ];

        foreach ($this->getSectionTypeCodes() as $typeCode) {
            $sectionOptions[] = $this->getExportStatesOption(
                'Only refresh "' . $typeCode . '" section data for products with those export states (%s)',
                false,
                sprintf(self::BASE_OPTION_KEY_SECTION_TYPE_EXPORT_STATES, $typeCode)
            );

            $sectionOptions[] = $this->getSelectedOnlyOption(
                'Only refresh "' . $typeCode . '" section data for selected products',
                sprintf(self::BASE_OPTION_KEY_SECTION_TYPE_SELECTED_ONLY, $typeCode)
            );

            $sectionOptions[] = $this->getRefreshStatesOption(
                'Only refresh "' . $typeCode . '" section data with those refresh states (%s)',
                false,
                sprintf(self::BASE_OPTION_KEY_SECTION_TYPE_REFRESH_STATES, $typeCode)
            );
        }

        $baseOptions = [
            $this->getStoresOption('Only refresh feed product data for those store IDs'),
            $this->getExportStatesOption('Only refresh data for products with those export states (%s) (overridable)'),
            $this->getSelectedOnlyOption('Only refresh data for selected products (overridable)'),
            $this->getRefreshStatesOption('Only refresh data with those refresh states (%s) (overridable)'),
        ];

        $this->setDefinition(array_merge($baseOptions, $exportStateOptions, $sectionOptions));
        parent::configure();
    }

    protected function executeActions(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $storeCollection = $this->getStoresOptionCollection($input);
            $storeIds = $storeCollection->getLoadedIds();

            $defaultExportStates = $this->getExportStatesOptionValue($input);
            $defaultSelectedOnly = $this->getSelectedOnlyOptionValue($input);
            $defaultRefreshStates = $this->getRefreshStatesOptionValue($input);

            if ($input->getOption(self::OPTION_KEY_REFRESH_EXPORT_STATE)) {
                $exportStates = $this->getExportStatesOptionValue($input, self::OPTION_KEY_EXPORT_STATE_EXPORT_STATES);
                $selectedOnly = $this->getSelectedOnlyOptionValue($input, self::OPTION_KEY_EXPORT_STATE_SELECTED_ONLY);
                $refreshStates = $this->getRefreshStatesOptionValue(
                    $input,
                    self::OPTION_KEY_EXPORT_STATE_REFRESH_STATES
                );

                $exportStateProductFilter = $this->createFeedProductFilter()
                    ->setExportStates(empty($exportStates) ? $defaultExportStates : $exportStates)
                    ->setSelectedOnly($defaultSelectedOnly || $selectedOnly)
                    ->setExportStateRefreshStates(empty($refreshStates) ? $defaultRefreshStates : $refreshStates);
            } else {
                $exportStateProductFilter = null;
            }

            $refreshedSectionTypes = $this->getSectionTypesOptionValue($input, self::OPTION_KEY_REFRESH_SECTION_TYPES);
            $sectionTypeProductFilters = [];
            $sectionTypeSectionFilters = [];

            foreach ($refreshedSectionTypes as $sectionType) {
                $typeId = $sectionType->getId();
                $typeCode = $sectionType->getCode();

                $exportStates = $this->getExportStatesOptionValue(
                    $input,
                    sprintf(self::BASE_OPTION_KEY_SECTION_TYPE_EXPORT_STATES, $typeCode)
                );

                $selectedOnly = $this->getSelectedOnlyOptionValue(
                    $input,
                    sprintf(self::BASE_OPTION_KEY_SECTION_TYPE_SELECTED_ONLY, $typeCode)
                );

                $refreshStates = $this->getRefreshStatesOptionValue(
                    $input,
                    sprintf(self::BASE_OPTION_KEY_SECTION_TYPE_REFRESH_STATES, $typeCode)
                );

                $sectionTypeProductFilters[$typeId] = $this->createFeedProductFilter()
                    ->setExportStates(empty($exportStates) ? $defaultExportStates : $exportStates)
                    ->setSelectedOnly($defaultSelectedOnly || $selectedOnly);

                $sectionTypeSectionFilters[$typeId] = $this->createFeedSectionFilter()
                    ->setRefreshStates(empty($refreshStates) ? $defaultRefreshStates : $refreshStates);
            }

            $io->title('Refreshing feed for store IDs: ' . implode(', ', $storeIds));
            $io->progressStart(count($storeIds));

            foreach ($storeCollection as $store) {
                $this->feedRefresher->refreshProducts(
                    $store,
                    $exportStateProductFilter,
                    $sectionTypeProductFilters,
                    $sectionTypeSectionFilters
                );

                $io->progressAdvance(1);
            }
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}
