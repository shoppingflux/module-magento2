<?php

namespace ShoppingFeed\Manager\Model\Command\Feed;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Api\Data\Feed\ProductInterface as FeedProductInterface;
use ShoppingFeed\Manager\Model\Command\ConfigInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\TypePoolInterface as SectionTypePoolInterface;
use ShoppingFeed\Manager\Model\Feed\Product\SectionFilterFactory as FeedSectionFilterFactory;
use ShoppingFeed\Manager\Model\Feed\ProductFilterFactory as FeedProductFilterFactory;
use ShoppingFeed\Manager\Model\Feed\Refresher as FeedRefresher;
use ShoppingFeed\Manager\Model\TimeFilterFactory;

class Refresh extends AbstractCommand
{
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
     */
    public function __construct(
        ConfigInterface $config,
        SectionTypePoolInterface $sectionTypePool,
        TimeFilterFactory $timeFilterFactory,
        FeedProductFilterFactory $feedProductFilterFactory,
        FeedSectionFilterFactory $feedSectionFilterFactory,
        FeedRefresher $feedRefresher
    ) {
        $this->feedRefresher = $feedRefresher;

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
        return __('Refresh Data');
    }

    /**
     * @param DataObject $configData
     * @throws LocalizedException
     */
    public function run(DataObject $configData)
    {
        $emptyProductFilter = $this->createFeedProductFilter();
        $exportStateProductFilter = $this->createFeedProductFilter();

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

        foreach ($this->getConfig()->getStores($configData) as $store) {
            $this->feedRefresher->refreshProducts(
                $store,
                $exportStateProductFilter,
                array_fill_keys($sectionTypeIds, $emptyProductFilter),
                array_fill_keys($sectionTypeIds, $sectionTypeSectionFilter)
            );
        }
    }
}
