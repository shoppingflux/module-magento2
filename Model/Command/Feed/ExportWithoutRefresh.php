<?php

namespace ShoppingFeed\Manager\Model\Command\Feed;

use Magento\Framework\DataObject;
use ShoppingFeed\Manager\Api\Account\StoreRepositoryInterface;
use ShoppingFeed\Manager\Model\Command\ConfigInterface;
use ShoppingFeed\Manager\Model\Feed\Exporter as FeedExporter;
use ShoppingFeed\Manager\Model\Feed\Product\Section\TypePoolInterface as SectionTypePoolInterface;
use ShoppingFeed\Manager\Model\Feed\Product\SectionFilterFactory as FeedSectionFilterFactory;
use ShoppingFeed\Manager\Model\Feed\ProductFilterFactory as FeedProductFilterFactory;
use ShoppingFeed\Manager\Model\TimeFilterFactory;

class ExportWithoutRefresh extends AbstractCommand
{
    /**
     * @var FeedExporter
     */
    private $feedExporter;

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @param ConfigInterface $config
     * @param SectionTypePoolInterface $sectionTypePool
     * @param TimeFilterFactory $timeFilterFactory
     * @param FeedProductFilterFactory $feedProductFilterFactory
     * @param FeedSectionFilterFactory $feedSectionFilterFactory
     * @param FeedExporter $feedExporter
     * @param StoreRepositoryInterface $storeRepository
     */
    public function __construct(
        ConfigInterface $config,
        SectionTypePoolInterface $sectionTypePool,
        TimeFilterFactory $timeFilterFactory,
        FeedProductFilterFactory $feedProductFilterFactory,
        FeedSectionFilterFactory $feedSectionFilterFactory,
        FeedExporter $feedExporter,
        StoreRepositoryInterface $storeRepository
    ) {
        $this->feedExporter = $feedExporter;
        $this->storeRepository = $storeRepository;

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
        return __('Export Feed (Without Refresh)');
    }

    public function run(DataObject $configData)
    {
        foreach ($this->getConfig()->getStores($configData) as $store) {
            $this->feedExporter->exportStoreFeed($store);
        }
    }
}
