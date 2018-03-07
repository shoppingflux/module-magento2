<?php

namespace ShoppingFeed\Manager\Console\Command\Feed;

use Magento\Framework\App\State as AppState;
use Magento\Framework\Console\Cli;
use ShoppingFeed\Manager\Console\Command\Exception as CommandException;
use ShoppingFeed\Manager\Model\Feed\Product\Export\State\ConfigInterface as ExportStateConfigInterface;
use ShoppingFeed\Manager\Model\Feed\Product\FilterFactory as FeedProductFilterFactory;
use ShoppingFeed\Manager\Model\Feed\Product\Section\FilterFactory as FeedSectionFilterFactory;
use ShoppingFeed\Manager\Model\Feed\Product\Section\TypePoolInterface as SectionTypePoolInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\CollectionFactory as StoreCollectionFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Refresher as RefresherResource;
use ShoppingFeed\Manager\Model\Time\Filter as TimeFilter;
use ShoppingFeed\Manager\Model\Time\FilterFactory as TimeFilterFactory;
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
     * @var RefresherResource
     */
    private $refresherResource;

    /**
     * @param AppState $appState
     * @param StoreCollectionFactory $storeCollectionFactory
     * @param SectionTypePoolInterface $sectionTypePool
     * @param TimeFilterFactory $timeFilterFactory
     * @param FeedProductFilterFactory $feedProductFilterFactory
     * @param FeedSectionFilterFactory $feedSectionFilterFactory
     * @param ExportStateConfigInterface $exportStateConfig
     * @param RefresherResource $refresherResource
     */
    public function __construct(
        AppState $appState,
        StoreCollectionFactory $storeCollectionFactory,
        SectionTypePoolInterface $sectionTypePool,
        TimeFilterFactory $timeFilterFactory,
        FeedProductFilterFactory $feedProductFilterFactory,
        FeedSectionFilterFactory $feedSectionFilterFactory,
        ExportStateConfigInterface $exportStateConfig,
        RefresherResource $refresherResource
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
        $this->refresherResource = $refresherResource;
    }

    protected function configure()
    {
        $this->setName('shoppingfeed:feed:force-automatic-refresh');
        $this->setDescription('Force automatic refresh of feed product data based on the store configurations');

        $this->setDefinition(
            [
                $this->getStoreIdsOption('Force automatic refresh for those store IDs'),

                $this->getFlagOption(
                    self::OPTION_KEY_FORCE_EXPORT_STATE_REFRESH,
                    'Force automatic refresh for product export states'
                ),

                $this->getSectionTypesOptions(
                    'Force automatic refresh for those section types (%s)',
                    true,
                    false,
                    self::OPTION_KEY_FORCE_SECTION_TYPES_REFRESH
                ),
            ]
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $storeCollection = $this->getStoreCollection($input);
            $storeIds = $storeCollection->getAllIds();

            $io->title('Forcing automatic refresh for store IDs: ' . implode(', ', $storeIds));
            $io->progressStart(count($storeIds));

            foreach ($storeCollection as $store) {
                if ($input->getOption(self::OPTION_KEY_FORCE_EXPORT_STATE_REFRESH)) { // @todo getFlagValue
                    $refreshState = $this->exportStateConfig->getAutomaticRefreshState($store);

                    if (false !== $refreshState) {
                        $productFilter = $this->createFeedProductFilter();
                        $overridableRefreshStates = $this->refresherResource->getOverridableRefreshStates($refreshState);

                        if (!empty($overridableRefreshStates)) {
                            $lastRefreshTimeFilter = $this->createTimeFilter()
                                ->setMode(TimeFilter::MODE_BEFORE)
                                ->setSeconds($this->exportStateConfig->getAutomaticRefreshDelay($store));

                            $productFilter
                                ->setStoreIds([ $store->getId() ])
                                ->setExportStateRefreshStates($overridableRefreshStates)
                                ->setLastExportStateRefreshTimeFilter($lastRefreshTimeFilter);

                            $this->refresherResource->forceProductExportStateRefresh($refreshState, $productFilter);
                        }
                    }
                }

                $emptyProductFilter = $this->createFeedProductFilter();
                $sectionTypes = $this->getSectionTypes($input, true, self::OPTION_KEY_FORCE_SECTION_TYPES_REFRESH);

                foreach ($sectionTypes as $sectionType) {
                    $typeConfig = $sectionType->getConfig();
                    $refreshState = $sectionType->getConfig()->getAutomaticRefreshState($store);

                    if (false !== $refreshState) {
                        $sectionFilter = $this->createFeedSectionFilter();
                        $overridableRefreshStates = $this->refresherResource->getOverridableRefreshStates($refreshState);

                        if (!empty($overridableRefreshStates)) {
                            $lastRefreshTimeFilter = $this->createTimeFilter()
                                ->setMode(TimeFilter::MODE_BEFORE)
                                ->setSeconds($typeConfig->getAutomaticRefreshDelay($store));

                            $sectionFilter
                                ->setStoreIds([ $store->getId() ])
                                ->setRefreshStates($overridableRefreshStates)
                                ->setLastRefreshTimeFilter($lastRefreshTimeFilter);

                            $this->refresherResource->forceProductSectionRefresh(
                                $refreshState,
                                $sectionFilter,
                                $emptyProductFilter
                            );
                        }
                    }
                }

                $io->progressAdvance(1);
            }

            $io->newLine(2);
            $io->success('Successfully forced automatic refresh.');
        } catch (CommandException $e) {
            $io->error($e->getMessage());
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}
