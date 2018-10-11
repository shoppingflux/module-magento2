<?php

namespace ShoppingFeed\Manager\Model\Command\Feed;

use Magento\Framework\DataObject;
use ShoppingFeed\Manager\Model\Command\ConfigInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\TypePoolInterface as SectionTypePoolInterface;
use ShoppingFeed\Manager\Model\Feed\Product\SectionFilterFactory as FeedSectionFilterFactory;
use ShoppingFeed\Manager\Model\Feed\ProductFilterFactory as FeedProductFilterFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Account\StoreFactory as StoreResourceFactory;
use ShoppingFeed\Manager\Model\TimeFilterFactory;

class SyncProductList extends AbstractCommand
{
    /**
     * @var StoreResourceFactory
     */
    private $storeResourceFactory;

    /**
     * @param ConfigInterface $config
     * @param SectionTypePoolInterface $sectionTypePool
     * @param TimeFilterFactory $timeFilterFactory
     * @param FeedProductFilterFactory $feedProductFilterFactory
     * @param FeedSectionFilterFactory $feedSectionFilterFactory
     * @param StoreResourceFactory $storeResourceFactory
     */
    public function __construct(
        ConfigInterface $config,
        SectionTypePoolInterface $sectionTypePool,
        TimeFilterFactory $timeFilterFactory,
        FeedProductFilterFactory $feedProductFilterFactory,
        FeedSectionFilterFactory $feedSectionFilterFactory,
        StoreResourceFactory $storeResourceFactory
    ) {
        $this->storeResourceFactory = $storeResourceFactory;

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
        return __('Synchronize Product List');
    }

    public function run(DataObject $configData)
    {
        $storeResource = $this->storeResourceFactory->create();

        foreach ($this->getConfig()->getStores($configData) as $store) {
            $storeResource->synchronizeFeedProductList($store->getId());
        }
    }
}
