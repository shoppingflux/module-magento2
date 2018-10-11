<?php

namespace ShoppingFeed\Manager\Observer;

use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use ShoppingFeed\Manager\Api\Data\Feed\ProductInterface as FeedProductInterface;
use ShoppingFeed\Manager\Model\Feed\ProductFilterFactory;
use ShoppingFeed\Manager\Model\Feed\Product\SectionFilterFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Refresher as FeedRefresher;
use ShoppingFeed\Manager\Model\ResourceModel\Account\StoreFactory as StoreResourceFactory;

class AfterProductSaveObserver implements ObserverInterface
{
    const EVENT_KEY_PRODUCT = 'product';

    /**
     * @var StoreResourceFactory
     */
    private $storeResourceFactory;

    /**
     * @var FeedRefresher
     */
    private $feedRefresher;

    /**
     * @var ProductFilterFactory
     */
    private $productFilterFactory;

    /**
     * @var SectionFilterFactory
     */
    private $sectionFilterFactory;

    /**
     * @param StoreResourceFactory $storeResourceFactory
     * @param FeedRefresher $feedRefresher
     * @param ProductFilterFactory $productFilterFactory
     * @param SectionFilterFactory $sectionFilterFactory
     */
    public function __construct(
        StoreResourceFactory $storeResourceFactory,
        FeedRefresher $feedRefresher,
        ProductFilterFactory $productFilterFactory,
        SectionFilterFactory $sectionFilterFactory
    ) {
        $this->storeResourceFactory = $storeResourceFactory;
        $this->feedRefresher = $feedRefresher;
        $this->productFilterFactory = $productFilterFactory;
        $this->sectionFilterFactory = $sectionFilterFactory;
    }

    public function execute(Observer $observer)
    {
        if (($catalogProduct = $observer->getEvent()->getData(static::EVENT_KEY_PRODUCT))
            && ($catalogProduct instanceof CatalogProduct)
            && ($productId = (int) $catalogProduct->getId())
        ) {
            try {
                $storeResource = $this->storeResourceFactory->create();
                $storeIds = $storeResource->getStoreIds();

                foreach ($storeIds as $storeId) {
                    $storeResource->synchronizeFeedProductList($storeId, [ $productId ]);
                }

                $productFilter = $this->productFilterFactory->create();
                $productFilter->setProductIds([ $productId ]);

                $sectionFilter = $this->sectionFilterFactory->create();
                $sectionFilter->setProductIds([ $productId ]);

                $this->feedRefresher->forceProductExportStateRefresh(
                    FeedProductInterface::REFRESH_STATE_REQUIRED,
                    $productFilter
                );

                $this->feedRefresher->forceProductSectionRefresh(
                    FeedProductInterface::REFRESH_STATE_REQUIRED,
                    $sectionFilter,
                    $productFilter
                );
            } catch (\Exception $e) {
                // Synchronizing the feed should not prevent from saving the product.
            }
        }
    }
}
