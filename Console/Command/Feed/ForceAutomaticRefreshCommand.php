<?php

namespace ShoppingFeed\Manager\Console\Command\Feed;

use Magento\Framework\App\State as AppState;
use Magento\Framework\Console\Cli;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Export\State\ConfigInterface as ExportStateConfigInterface;
use ShoppingFeed\Manager\Model\Feed\ProductFilterFactory as FeedProductFilterFactory;
use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractType as SectionType;
use ShoppingFeed\Manager\Model\Feed\Product\SectionFilterFactory as FeedSectionFilterFactory;
use ShoppingFeed\Manager\Model\Feed\Product\Section\TypePoolInterface as SectionTypePoolInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\CollectionFactory as StoreCollectionFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\RefresherFactory as RefresherResourceFactory;
use ShoppingFeed\Manager\Model\TimeFilter;
use ShoppingFeed\Manager\Model\TimeFilterFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ForceAutomaticRefreshCommand extends AbstractCommand
{
    const OPTION_KEY_FORCE_EXPORT_STATE_REFRESH = 'force_export_state_refresh';
    const OPTION_KEY_FORCE_SECTION_TYPES_REFRESH = 'force_section_type_refresh';

    /**
     * @var ExportStateConfigInterface
     */
    private $exportStateConfig;

    /**
     * @var RefresherResourceFactory
     */
    private $refresherResourceFactory;

    /**
     * @param AppState $appState
     * @param StoreCollectionFactory $storeCollectionFactory
     * @param SectionTypePoolInterface $sectionTypePool
     * @param TimeFilterFactory $timeFilterFactory
     * @param FeedProductFilterFactory $feedProductFilterFactory
     * @param FeedSectionFilterFactory $feedSectionFilterFactory
     * @param ExportStateConfigInterface $exportStateConfig
     * @param RefresherResourceFactory $refresherResourceFactory
     */
    public function __construct(
        AppState $appState,
        StoreCollectionFactory $storeCollectionFactory,
        SectionTypePoolInterface $sectionTypePool,
        TimeFilterFactory $timeFilterFactory,
        FeedProductFilterFactory $feedProductFilterFactory,
        FeedSectionFilterFactory $feedSectionFilterFactory,
        ExportStateConfigInterface $exportStateConfig,
        RefresherResourceFactory $refresherResourceFactory
    ) {
        parent::__construct(
            $appState,
            $storeCollectionFactory,
            $sectionTypePool,
            $timeFilterFactory,
            $feedProductFilterFactory,
            $feedSectionFilterFactory
        );

        $this->exportStateConfig = $exportStateConfig;
        $this->refresherResourceFactory = $refresherResourceFactory;
    }

    protected function configure()
    {
        $this->setName('shoppingfeed:feed:force-automatic-refresh');
        $this->setDescription('Force automatic refresh of feed product data based on the store configurations');

        $this->setDefinition(
            [
                $this->getStoresOption('Force automatic refresh for those store IDs'),

                $this->getFlagOption(
                    self::OPTION_KEY_FORCE_EXPORT_STATE_REFRESH,
                    'Force automatic refresh for product export states'
                ),

                $this->getSectionTypesOption(
                    'Force automatic refresh for those section types (%s)',
                    false,
                    self::OPTION_KEY_FORCE_SECTION_TYPES_REFRESH
                ),
            ]
        );

        parent::configure();
    }

    /**
     * @param StoreInterface $store
     * @param bool $refreshExportState
     * @param SectionType[] $refreshedSectionTypes
     */
    private function forceStoreFeedAutomaticRefresh(
        StoreInterface $store,
        $refreshExportState,
        array $refreshedSectionTypes
    ) {
        $refresherResource = $this->refresherResourceFactory->create();

        if ($refreshExportState) {
            $refreshState = $this->exportStateConfig->getAutomaticRefreshState($store);

            if (false !== $refreshState) {
                $productFilter = $this->createFeedProductFilter();
                $overridableRefreshStates = $refresherResource->getOverridableRefreshStates($refreshState);

                if (!empty($overridableRefreshStates)) {
                    $lastRefreshTimeFilter = $this->createTimeFilter()
                        ->setMode(TimeFilter::MODE_BEFORE)
                        ->setSeconds($this->exportStateConfig->getAutomaticRefreshDelay($store));

                    $productFilter
                        ->setStoreIds([ $store->getId() ])
                        ->setExportStateRefreshStates($overridableRefreshStates)
                        ->setLastExportStateRefreshTimeFilter($lastRefreshTimeFilter);

                    $refresherResource->forceProductExportStateRefresh($refreshState, $productFilter);
                }
            }
        }

        $emptyProductFilter = $this->createFeedProductFilter();

        foreach ($refreshedSectionTypes as $sectionType) {
            $typeConfig = $sectionType->getConfig();
            $refreshState = $sectionType->getConfig()->getAutomaticRefreshState($store);

            if (false !== $refreshState) {
                $sectionFilter = $this->createFeedSectionFilter();
                $overridableRefreshStates = $refresherResource->getOverridableRefreshStates($refreshState);

                if (!empty($overridableRefreshStates)) {
                    $lastRefreshTimeFilter = $this->createTimeFilter()
                        ->setMode(TimeFilter::MODE_BEFORE)
                        ->setSeconds($typeConfig->getAutomaticRefreshDelay($store));

                    $sectionFilter
                        ->setStoreIds([ $store->getId() ])
                        ->setRefreshStates($overridableRefreshStates)
                        ->setLastRefreshTimeFilter($lastRefreshTimeFilter);

                    $refresherResource->forceProductSectionRefresh(
                        $refreshState,
                        $sectionFilter,
                        $emptyProductFilter
                    );
                }
            }
        }
    }

    protected function executeActions(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $storeCollection = $this->getStoresOptionCollection($input);
            $storeIds = $storeCollection->getLoadedIds();

            $io->title('Forcing automatic refresh for store IDs: ' . implode(', ', $storeIds));
            $io->progressStart(count($storeIds));

            $refreshExportState = $this->getFlagOptionValue($input, self::OPTION_KEY_FORCE_EXPORT_STATE_REFRESH);
            $sectionTypes = $this->getSectionTypesOptionValue($input, self::OPTION_KEY_FORCE_SECTION_TYPES_REFRESH);

            foreach ($storeCollection as $store) {
                $this->forceStoreFeedAutomaticRefresh($store, $refreshExportState, $sectionTypes);
                $io->progressAdvance(1);
            }

            $io->newLine(2);
            $io->success('Successfully forced automatic refresh.');
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}
