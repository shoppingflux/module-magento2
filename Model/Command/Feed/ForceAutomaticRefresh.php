<?php

namespace ShoppingFeed\Manager\Model\Command\Feed;

use Magento\Framework\DataObject;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Command\ConfigInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Export\State\ConfigInterface as ExportStateConfigInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractType as SectionType;
use ShoppingFeed\Manager\Model\Feed\Product\Section\TypePoolInterface as SectionTypePoolInterface;
use ShoppingFeed\Manager\Model\Feed\Product\SectionFilterFactory as FeedSectionFilterFactory;
use ShoppingFeed\Manager\Model\Feed\ProductFilterFactory as FeedProductFilterFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\RefresherFactory as FeedRefresherResourceFactory;
use ShoppingFeed\Manager\Model\TimeFilter;
use ShoppingFeed\Manager\Model\TimeFilterFactory;

class ForceAutomaticRefresh extends AbstractCommand
{
    /**
     * @var ExportStateConfigInterface
     */
    private $exportStateConfig;

    /**
     * @var FeedRefresherResourceFactory
     */
    private $feedRefresherResourceFactory;

    /**
     * @param ConfigInterface $config
     * @param SectionTypePoolInterface $sectionTypePool
     * @param TimeFilterFactory $timeFilterFactory
     * @param FeedProductFilterFactory $feedProductFilterFactory
     * @param FeedSectionFilterFactory $feedSectionFilterFactory
     * @param ExportStateConfigInterface $exportStateConfig
     * @param FeedRefresherResourceFactory $feedRefresherResourceFactory
     */
    public function __construct(
        ConfigInterface $config,
        SectionTypePoolInterface $sectionTypePool,
        TimeFilterFactory $timeFilterFactory,
        FeedProductFilterFactory $feedProductFilterFactory,
        FeedSectionFilterFactory $feedSectionFilterFactory,
        ExportStateConfigInterface $exportStateConfig,
        FeedRefresherResourceFactory $feedRefresherResourceFactory
    ) {
        $this->exportStateConfig = $exportStateConfig;
        $this->feedRefresherResourceFactory = $feedRefresherResourceFactory;

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
        return __('Force Automatic Data Refresh');
    }

    /**
     * @param StoreInterface $store
     */
    private function forceStoreFeedAutomaticExportStateRefresh(StoreInterface $store)
    {
        $feedRefresherResource = $this->feedRefresherResourceFactory->create();
        $refreshState = $this->exportStateConfig->getAutomaticRefreshState($store);

        if (false !== $refreshState) {
            $productFilter = $this->createFeedProductFilter();
            $overridableRefreshStates = $feedRefresherResource->getOverridableRefreshStates($refreshState);

            if (!empty($overridableRefreshStates)) {
                $lastRefreshTimeFilter = $this->createTimeFilter()
                    ->setMode(TimeFilter::MODE_BEFORE)
                    ->setSeconds($this->exportStateConfig->getAutomaticRefreshDelay($store));

                $productFilter
                    ->setStoreIds([ $store->getId() ])
                    ->setExportStateRefreshStates($overridableRefreshStates)
                    ->setLastExportStateRefreshTimeFilter($lastRefreshTimeFilter);

                $feedRefresherResource->forceProductExportStateRefresh($refreshState, $productFilter);
            }
        }
    }

    /**
     * @param StoreInterface $store
     * @param SectionType $sectionType
     */
    private function forceStoreFeedAutomaticSectionTypeRefresh(StoreInterface $store, SectionType $sectionType)
    {
        $feedRefresherResource = $this->feedRefresherResourceFactory->create();
        $emptyProductFilter = $this->createFeedProductFilter();
        $sectionTypeConfig = $sectionType->getConfig();
        $refreshState = $sectionType->getConfig()->getAutomaticRefreshState($store);

        if (false !== $refreshState) {
            $sectionFilter = $this->createFeedSectionFilter();
            $overridableRefreshStates = $feedRefresherResource->getOverridableRefreshStates($refreshState);

            if (!empty($overridableRefreshStates)) {
                $lastRefreshTimeFilter = $this->createTimeFilter()
                    ->setMode(TimeFilter::MODE_BEFORE)
                    ->setSeconds($sectionTypeConfig->getAutomaticRefreshDelay($store));

                $sectionFilter
                    ->setStoreIds([ $store->getId() ])
                    ->setRefreshStates($overridableRefreshStates)
                    ->setLastRefreshTimeFilter($lastRefreshTimeFilter);

                $feedRefresherResource->forceProductSectionRefresh(
                    $refreshState,
                    $sectionFilter,
                    $emptyProductFilter
                );
            }
        }
    }

    public function run(DataObject $configData)
    {
        foreach ($this->getConfig()->getStores($configData) as $store) {
            $this->forceStoreFeedAutomaticExportStateRefresh($store);

            foreach ($this->getSectionTypes() as $sectionType) {
                $this->forceStoreFeedAutomaticSectionTypeRefresh($store, $sectionType);
            }
        }
    }
}
