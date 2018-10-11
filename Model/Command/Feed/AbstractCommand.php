<?php

namespace ShoppingFeed\Manager\Model\Command\Feed;

use ShoppingFeed\Manager\Model\AbstractCommand as BaseCommand;
use ShoppingFeed\Manager\Model\Command\ConfigInterface;
use ShoppingFeed\Manager\Model\Feed\ProductFilter as FeedProductFilter;
use ShoppingFeed\Manager\Model\Feed\ProductFilterFactory as FeedProductFilterFactory;
use ShoppingFeed\Manager\Model\Feed\Product\SectionFilter as FeedSectionFilter;
use ShoppingFeed\Manager\Model\Feed\Product\SectionFilterFactory as FeedSectionFilterFactory;
use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractType as SectionType;
use ShoppingFeed\Manager\Model\Feed\Product\Section\TypePoolInterface as SectionTypePoolInterface;
use ShoppingFeed\Manager\Model\TimeFilter;
use ShoppingFeed\Manager\Model\TimeFilterFactory;

// @todo code duplication between CLI commands and command models is temporary (until the first are based on the second)
abstract class AbstractCommand extends BaseCommand
{
    /**
     * @var SectionTypePoolInterface
     */
    private $sectionTypePool;

    /**
     * @var TimeFilterFactory
     */
    private $timeFilterFactory;

    /**
     * @var FeedProductFilterFactory
     */
    private $feedProductFilterFactory;

    /**
     * @var FeedSectionFilterFactory
     */
    private $feedSectionFilterFactory;

    /**
     * @param ConfigInterface $config
     * @param SectionTypePoolInterface $sectionTypePool
     * @param TimeFilterFactory $timeFilterFactory
     * @param FeedProductFilterFactory $feedProductFilterFactory
     * @param FeedSectionFilterFactory $feedSectionFilterFactory
     */
    public function __construct(
        ConfigInterface $config,
        SectionTypePoolInterface $sectionTypePool,
        TimeFilterFactory $timeFilterFactory,
        FeedProductFilterFactory $feedProductFilterFactory,
        FeedSectionFilterFactory $feedSectionFilterFactory
    ) {
        $this->sectionTypePool = $sectionTypePool;
        $this->timeFilterFactory = $timeFilterFactory;
        $this->feedProductFilterFactory = $feedProductFilterFactory;
        $this->feedSectionFilterFactory = $feedSectionFilterFactory;
        parent::__construct($config);
    }

    /**
     * @return int[]
     */
    public function getSectionTypeIds()
    {
        return $this->sectionTypePool->getTypeIds();
    }

    /**
     * @return string[]
     */
    public function getSectionTypeCodes()
    {
        return $this->sectionTypePool->getTypeCodes();
    }

    /**
     * @return SectionType[]
     */
    public function getSectionTypes()
    {
        return $this->sectionTypePool->getTypes();
    }

    /**
     * @return TimeFilter
     */
    public function createTimeFilter()
    {
        return $this->timeFilterFactory->create();
    }

    /**
     * @return FeedProductFilter
     */
    public function createFeedProductFilter()
    {
        return $this->feedProductFilterFactory->create();
    }

    /**
     * @return FeedSectionFilter
     */
    public function createFeedSectionFilter()
    {
        return $this->feedSectionFilterFactory->create();
    }
}
