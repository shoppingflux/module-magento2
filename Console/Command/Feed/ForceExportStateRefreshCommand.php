<?php

namespace ShoppingFeed\Manager\Console\Command\Feed;

use Magento\Framework\App\State as AppState;
use Magento\Framework\Console\Cli;
use ShoppingFeed\Manager\Model\Feed\Product\FilterFactory as FeedProductFilterFactory;
use ShoppingFeed\Manager\Model\Feed\Product\Section\FilterFactory as FeedSectionFilterFactory;
use ShoppingFeed\Manager\Model\Feed\Product\Section\TypePoolInterface as SectionTypePoolInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\CollectionFactory as StoreCollectionFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Refresher as RefresherResource;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\RefresherFactory as RefresherResourceFactory;
use ShoppingFeed\Manager\Model\Time\FilterFactory as TimeFilterFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


class ForceExportStateRefreshCommand extends AbstractCommand
{
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
     * @param RefresherResourceFactory $refresherResourceFactory
     */
    public function __construct(
        AppState $appState,
        StoreCollectionFactory $storeCollectionFactory,
        SectionTypePoolInterface $sectionTypePool,
        TimeFilterFactory $timeFilterFactory,
        FeedProductFilterFactory $feedProductFilterFactory,
        FeedSectionFilterFactory $feedSectionFilterFactory,
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

        $this->refresherResource = $refresherResourceFactory->create();
    }

    protected function configure()
    {
        $this->setName('shoppingfeed:feed:force-export-state-refresh');
        $this->setDescription('Marks the export state of some feed products as refreshable');

        $this->setDefinition(
            [
                $this->getRefreshStateArgument('The refresh state to force (%s)'),
                $this->getStoresOption('Only force refresh for those store IDs'),
                $this->getExportStatesOption('Only refresh products with those export states (%s)'),
                $this->getSelectedOnlyOption('Only refresh selected products'),
            ]
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $storeCollection = $this->getStoresOptionCollection($input);
            $storeIds = $storeCollection->getAllIds();
            $refreshState = $this->getRefreshStateArgumentValue($input);
            $overridableRefreshStates = $this->refresherResource->getOverridableRefreshStates($refreshState);

            $io->title('Forcing export state refresh for store IDs: ' . implode(', ', $storeIds));
            $io->progressStart(count($storeIds));

            $productFilter = $this->createFeedProductFilter();
            $productFilter->setSelectedOnly($this->getSelectedOnlyOptionValue($input));
            $productFilter->setExportStates($this->getExportStatesOptionValue($input));
            $productFilter->setExportStateRefreshStates($overridableRefreshStates);

            foreach ($storeCollection as $store) {
                $productFilter->setStoreIds([ $store->getId() ]);
                $this->refresherResource->forceProductExportStateRefresh($refreshState, $productFilter);
                $io->progressAdvance(1);
            }

            $io->newLine(2);
            $io->success('Successfully forced export state refresh.');
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}
