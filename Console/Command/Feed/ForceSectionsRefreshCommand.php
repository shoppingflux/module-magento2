<?php

namespace ShoppingFeed\Manager\Console\Command\Feed;

use Magento\Framework\App\State as AppState;
use Magento\Framework\Console\Cli;
use ShoppingFeed\Manager\Model\Feed\ProductFilterFactory as FeedProductFilterFactory;
use ShoppingFeed\Manager\Model\Feed\Product\SectionFilterFactory as FeedSectionFilterFactory;
use ShoppingFeed\Manager\Model\Feed\Product\Section\TypePoolInterface as SectionTypePoolInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\CollectionFactory as StoreCollectionFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\RefresherFactory as RefresherResourceFactory;
use ShoppingFeed\Manager\Model\TimeFilterFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ForceSectionsRefreshCommand extends AbstractCommand
{
    /**
     * @var RefresherResourceFactory
     */
    private $refresherResourceFactory;

    const OPTION_KEY_UPDATED_IN_CATALOG_ONLY = 'updated_in_catalog_only';

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

        $this->refresherResourceFactory = $refresherResourceFactory;
    }

    protected function configure()
    {
        $this->setName('shoppingfeed:feed:force-sections-refresh');
        $this->setDescription('Marks some feed product sections as refreshable');

        $this->setDefinition(
            [
                $this->getRefreshStateArgument('The refresh state to force (%s)'),
                $this->getStoresOption('Only force refresh for those store IDs'),
                $this->getSectionTypesOption('Only force refresh for those section types (%s)'),
                $this->getExportStatesOption('Only force refresh for products with those export states (%s)'),
                $this->getSelectedOnlyOption('Only force refresh for selected products'),
                $this->getFlagOption(
                    self::OPTION_KEY_UPDATED_IN_CATALOG_ONLY,
                    'Only force refresh for products which have been updated in the catalog'
                ),
            ]
        );

        parent::configure();
    }

    protected function executeActions(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $refresherResource = $this->refresherResourceFactory->create();

        try {
            $storeCollection = $this->getStoresOptionCollection($input);
            $storeIds = $storeCollection->getLoadedIds();
            $refreshState = $this->getRefreshStateArgumentValue($input);
            $overridableRefreshStates = $refresherResource->getOverridableRefreshStates($refreshState);
            $updatedInCatalogOnly = $this->getFlagOptionValue($input, self::OPTION_KEY_UPDATED_IN_CATALOG_ONLY);

            $io->title('Forcing product sections refresh for store IDs: ' . implode(', ', $storeIds));
            $io->progressStart(count($storeIds));

            $productFilter = $this->createFeedProductFilter();
            $productFilter->setSelectedOnly($this->getSelectedOnlyOptionValue($input));
            $productFilter->setExportStates($this->getExportStatesOptionValue($input));

            $sectionFilter = $this->createFeedSectionFilter();
            $sectionFilter->setTypeIds($this->getSectionTypesOptionIds($input));
            $sectionFilter->setRefreshStates($overridableRefreshStates);

            foreach ($storeCollection as $store) {
                $sectionFilter->setStoreIds([ $store->getId() ]);

                $refresherResource->forceProductSectionRefresh(
                    $refreshState,
                    $sectionFilter,
                    $productFilter,
                    $updatedInCatalogOnly
                );

                $io->progressAdvance(1);
            }

            $io->newLine(2);
            $io->success('Successfully forced product sections refresh.');
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}
