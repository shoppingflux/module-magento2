<?php

namespace ShoppingFeed\Manager\Console\Command\Feed;

use Magento\Framework\App\State as AppState;
use Magento\Framework\Console\Cli;
use Magento\Framework\Config\ScopeInterface as ConfigScopeInterface;
use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Api\Data\Feed\ProductInterface as FeedProductInterface;
use ShoppingFeed\Manager\Model\Feed\Exporter as FeedExporter;
use ShoppingFeed\Manager\Model\Feed\ProductFilterFactory as FeedProductFilterFactory;
use ShoppingFeed\Manager\Model\Feed\Product\SectionFilterFactory as FeedSectionFilterFactory;
use ShoppingFeed\Manager\Model\Feed\Product\Section\TypePoolInterface as SectionTypePoolInterface;
use ShoppingFeed\Manager\Model\Feed\Refresher as FeedRefresher;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\CollectionFactory as StoreCollectionFactory;
use ShoppingFeed\Manager\Model\TimeFilterFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ExportCommand extends AbstractCommand
{
    const OPTION_KEY_SKIP_REFRESH = 'skip_refresh';

    /**
     * @var FeedRefresher
     */
    private $feedRefresher;

    /**
     * @var FeedExporter
     */
    private $feedExporter;

    /**
     * @param AppState $appState
     * @param ConfigScopeInterface $configScope
     * @param StoreCollectionFactory $storeCollectionFactory
     * @param SectionTypePoolInterface $sectionTypePool
     * @param TimeFilterFactory $timeFilterFactory
     * @param FeedProductFilterFactory $feedProductFilterFactory
     * @param FeedSectionFilterFactory $feedSectionFilterFactory
     * @param FeedRefresher $feedRefresher
     * @param FeedExporter $feedExporter
     */
    public function __construct(
        AppState $appState,
        ConfigScopeInterface $configScope,
        StoreCollectionFactory $storeCollectionFactory,
        SectionTypePoolInterface $sectionTypePool,
        TimeFilterFactory $timeFilterFactory,
        FeedProductFilterFactory $feedProductFilterFactory,
        FeedSectionFilterFactory $feedSectionFilterFactory,
        FeedRefresher $feedRefresher,
        FeedExporter $feedExporter
    ) {
        parent::__construct(
            $appState,
            $configScope,
            $storeCollectionFactory,
            $sectionTypePool,
            $timeFilterFactory,
            $feedProductFilterFactory,
            $feedSectionFilterFactory
        );

        $this->feedRefresher = $feedRefresher;
        $this->feedExporter = $feedExporter;
    }

    protected function configure()
    {
        $this->setName('shoppingfeed:feed:export');
        $this->setDescription('Generates a new feed for one or more accounts');

        $this->setDefinition(
            [
                $this->getAccountsOption('Only generate the feed for these account IDs'),
                $this->getStoresOption('Only generate the feed for these account IDs'),
                $this->getFlagOption(
                    self::OPTION_KEY_SKIP_REFRESH,
                    'Do not refresh product export states and data before generating the feed'
                ),
            ]
        );

        parent::configure();
    }

    /**
     * @param StoreInterface $store
     * @throws LocalizedException
     */
    private function refreshStoreFeedData(StoreInterface $store)
    {
        $exportStateProductFilter = $this->createFeedProductFilter();

        $exportStateProductFilter->setExportStateRefreshStates(
            [ FeedProductInterface::REFRESH_STATE_REQUIRED ]
        );

        $sectionTypeIds = $this->getSectionTypeIds();
        $sectionTypeProductFilter = $this->createFeedProductFilter();
        $sectionTypeSectionFilter = $this->createFeedSectionFilter();
        $sectionTypeSectionFilter->setRefreshStates([ FeedProductInterface::REFRESH_STATE_REQUIRED ]);

        $this->feedRefresher->refreshProducts(
            $store,
            $exportStateProductFilter,
            array_fill_keys($sectionTypeIds, $sectionTypeProductFilter),
            array_fill_keys($sectionTypeIds, $sectionTypeSectionFilter)
        );
    }

    /**
     * @param StoreInterface $store
     * @throws \Exception
     * @throws LocalizedException
     */
    private function exportStoreFeed(StoreInterface $store)
    {
        $this->feedExporter->exportStoreFeed($store);
    }

    protected function executeActions(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $storeCollection = $this->getStoresOptionCollection($input);
            $storeIds = $storeCollection->getLoadedIds();

            $io->title('Generating the feed for account IDs: ' . implode(', ', $storeIds));
            $io->progressStart(count($storeIds));

            foreach ($storeCollection as $store) {
                if (!$this->getFlagOptionValue($input, self::OPTION_KEY_SKIP_REFRESH)) {
                    $this->refreshStoreFeedData($store);
                }

                $this->exportStoreFeed($store);
                $io->progressAdvance(1);
            }

            $io->newLine(2);
            $io->success('Successfully generated the feeds.');
            $io->progressFinish();
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}
