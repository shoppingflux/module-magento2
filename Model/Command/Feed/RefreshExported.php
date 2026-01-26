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
use ShoppingFeed\Manager\Model\Feed\Product\Section\TypePoolInterface as SectionTypePoolInterface;
use ShoppingFeed\Manager\Model\Feed\Product\SectionFilterFactory as FeedSectionFilterFactory;
use ShoppingFeed\Manager\Model\Feed\ProductFilterFactory as FeedProductFilterFactory;
use ShoppingFeed\Manager\Model\Feed\Refresher as FeedRefresher;
use ShoppingFeed\Manager\Model\TimeFilterFactory;

class RefreshExported extends AbstractCommand
{
    use WithFeedRefreshTrait;

    /**
     * @var FeedRefresher
     */
    private $feedRefresher;

    /**
     * @param ConfigInterface $config
     * @param SectionTypePoolInterface $sectionTypePool
     * @param TimeFilterFactory $timeFilterFactory
     * @param FeedProductFilterFactory $feedProductFilterFactory
     * @param FeedSectionFilterFactory $feedSectionFilterFactory
     * @param FeedRefresher $feedRefresher
     * @param StoreRepositoryInterface|null $storeRepository
     */
    public function __construct(
        ConfigInterface $config,
        SectionTypePoolInterface $sectionTypePool,
        TimeFilterFactory $timeFilterFactory,
        FeedProductFilterFactory $feedProductFilterFactory,
        FeedSectionFilterFactory $feedSectionFilterFactory,
        FeedRefresher $feedRefresher,
        ?StoreRepositoryInterface $storeRepository = null
    ) {
        $this->feedRefresher = $feedRefresher;
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
        return __('Refresh Data (Exported Products Only)');
    }

    /**
     * @param DataObject $configData
     * @throws LocalizedException
     */
    public function run(DataObject $configData)
    {
        $exportedProductFilter = $this->createFeedProductFilter();

        $exportedProductFilter->setExportStates(
            [
                FeedProductInterface::STATE_EXPORTED,
            ]
        );

        $exportedProductFilter->setChildExportStates(
            [
                FeedProductInterface::STATE_EXPORTED,
            ]
        );

        $exportStateProductFilter = clone $exportedProductFilter;

        $exportStateProductFilter->setExportStateRefreshStates(
            [
                FeedProductInterface::REFRESH_STATE_ADVISED,
                FeedProductInterface::REFRESH_STATE_REQUIRED,
            ]
        );

        $sectionTypeIds = $this->getSectionTypeIds();
        $sectionTypeSectionFilter = $this->createFeedSectionFilter();

        $sectionTypeSectionFilter->setRefreshStates(
            [
                FeedProductInterface::REFRESH_STATE_ADVISED,
                FeedProductInterface::REFRESH_STATE_REQUIRED,
            ]
        );

        $this->withPrioritizedStores(
            $configData,
            function (StoreInterface $store) use (
                $exportedProductFilter,
                $exportStateProductFilter,
                $sectionTypeIds,
                $sectionTypeSectionFilter
            ) {
                $this->feedRefresher->refreshProducts(
                    $store,
                    $exportStateProductFilter,
                    array_fill_keys($sectionTypeIds, $exportedProductFilter),
                    array_fill_keys($sectionTypeIds, $sectionTypeSectionFilter)
                );
            }
        );
    }
}
