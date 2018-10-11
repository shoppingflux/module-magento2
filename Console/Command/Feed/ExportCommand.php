<?php

namespace ShoppingFeed\Manager\Console\Command\Feed;

use Magento\Framework\App\State as AppState;
use Magento\Framework\Console\Cli;
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
        $this->setDescription('Exports the feed of one or more stores');
        $this->setDefinition([ $this->getStoresOption('Only export the feed for those store IDs') ]);
        parent::configure();
    }

    /**
     * @param StoreInterface $store
     * @throws \Exception
     * @throws LocalizedException
     */
    private function exportStoreFeed(StoreInterface $store)
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

        $this->feedExporter->exportStoreFeed($store);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $storeCollection = $this->getStoresOptionCollection($input);
            $storeIds = $storeCollection->getLoadedIds();

            $io->title('Exporting feed for store IDs: ' . implode(', ', $storeIds));
            $io->progressStart(count($storeIds));

            foreach ($storeCollection as $store) {
                $this->exportStoreFeed($store);
                $io->progressAdvance(1);
            }
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}
