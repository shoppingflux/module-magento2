<?php

namespace ShoppingFeed\Manager\Model\Command\Feed;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Api\Account\StoreRepositoryInterface;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Api\Data\Feed\ProductInterface as FeedProductInterface;
use ShoppingFeed\Manager\Model\Command\ConfigInterface;
use ShoppingFeed\Manager\Model\Command\Feed\Traits\WithFeedRefreshTrait;
use ShoppingFeed\Manager\Model\Feed\Exporter as FeedExporter;
use ShoppingFeed\Manager\Model\Feed\Product\Section\TypePoolInterface as SectionTypePoolInterface;
use ShoppingFeed\Manager\Model\Feed\Product\SectionFilterFactory as FeedSectionFilterFactory;
use ShoppingFeed\Manager\Model\Feed\ProductFilterFactory as FeedProductFilterFactory;
use ShoppingFeed\Manager\Model\Feed\Refresher as FeedRefresher;
use ShoppingFeed\Manager\Model\TimeFilterFactory;

class Export extends AbstractCommand
{
    use WithFeedRefreshTrait;

    /**
     * @var FeedRefresher
     */
    private $feedRefresher;

    /**
     * @var FeedExporter
     */
    private $feedExporter;

    /**
     * @param ConfigInterface $config
     * @param SectionTypePoolInterface $sectionTypePool
     * @param TimeFilterFactory $timeFilterFactory
     * @param FeedProductFilterFactory $feedProductFilterFactory
     * @param FeedSectionFilterFactory $feedSectionFilterFactory
     * @param FeedRefresher $feedRefresher
     * @param FeedExporter $feedExporter
     * @param StoreRepositoryInterface|null $storeRepository
     */
    public function __construct(
        ConfigInterface $config,
        SectionTypePoolInterface $sectionTypePool,
        TimeFilterFactory $timeFilterFactory,
        FeedProductFilterFactory $feedProductFilterFactory,
        FeedSectionFilterFactory $feedSectionFilterFactory,
        FeedRefresher $feedRefresher,
        FeedExporter $feedExporter,
        StoreRepositoryInterface $storeRepository = null
    ) {
        $this->feedRefresher = $feedRefresher;
        $this->feedExporter = $feedExporter;
        $this->storeRepository = $storeRepository ?: ObjectManager::getInstance()->get(StoreRepositoryInterface::class);

        parent::__construct(
            $config,
            $sectionTypePool,
            $timeFilterFactory,
            $feedProductFilterFactory,
            $feedSectionFilterFactory
        );
    }

    public function getLabel()
    {
        return __('Export Feed (With Refresh)');
    }

    /**
     * @param DataObject $configData
     * @throws \Exception
     * @throws LocalizedException
     */
    public function run(DataObject $configData)
    {
        $emptyProductFilter = $this->createFeedProductFilter();

        $exportStateProductFilter = $this->createFeedProductFilter();
        $exportStateProductFilter->setExportStateRefreshStates([ FeedProductInterface::REFRESH_STATE_REQUIRED ]);

        $sectionTypeIds = $this->getSectionTypeIds();
        $sectionTypeSectionFilter = $this->createFeedSectionFilter();
        $sectionTypeSectionFilter->setRefreshStates([ FeedProductInterface::REFRESH_STATE_REQUIRED ]);

        $this->withPrioritizedStores(
            $configData,
            function (StoreInterface $store) use (
                $emptyProductFilter,
                $exportStateProductFilter,
                $sectionTypeIds,
                $sectionTypeSectionFilter
            ) {
                $this->feedRefresher->refreshProducts(
                    $store,
                    $exportStateProductFilter,
                    array_fill_keys($sectionTypeIds, $emptyProductFilter),
                    array_fill_keys($sectionTypeIds, $sectionTypeSectionFilter)
                );

                $this->feedExporter->exportStoreFeed($store);
            }
        );
    }
}
