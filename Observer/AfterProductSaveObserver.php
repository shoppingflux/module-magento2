<?php

namespace ShoppingFeed\Manager\Observer;

use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use ShoppingFeed\Manager\Api\Data\Feed\ProductInterface as FeedProductInterface;
use ShoppingFeed\Manager\Model\Feed\Product\FilterFactory as ProductFilterFactory;
use ShoppingFeed\Manager\Model\Feed\Product\Section\FilterFactory as SectionFilterFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Refresher as FeedRefresher;


class AfterProductSaveObserver implements ObserverInterface
{
    const EVENT_KEY_PRODUCT = 'product';

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
     * @param FeedRefresher $feedRefresher
     * @param ProductFilterFactory $productFilterFactory
     * @param SectionFilterFactory $sectionFilterFactory
     */
    public function __construct(
        FeedRefresher $feedRefresher,
        ProductFilterFactory $productFilterFactory,
        SectionFilterFactory $sectionFilterFactory
    ) {
        $this->feedRefresher = $feedRefresher;
        $this->productFilterFactory = $productFilterFactory;
        $this->sectionFilterFactory = $sectionFilterFactory;
    }

    public function execute(Observer $observer)
    {
        if (($catalogProduct = $observer->getEvent()->getData(static::EVENT_KEY_PRODUCT))
            && ($catalogProduct instanceof CatalogProduct)
        ) {
            $productIds = [ $catalogProduct->getId() ];

            $productFilter = $this->productFilterFactory->create();
            $productFilter->setProductIds($productIds);

            $sectionFilter = $this->sectionFilterFactory->create();
            $sectionFilter->setProductIds($productIds);

            try {
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
                // Forcing a feed refresh should not prevent from saving the product.
            }
        }
    }
}
