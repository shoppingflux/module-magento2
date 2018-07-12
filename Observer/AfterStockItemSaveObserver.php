<?php

namespace ShoppingFeed\Manager\Observer;

use Magento\CatalogInventory\Model\Stock\Item as StockItem;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use ShoppingFeed\Manager\Api\Data\Feed\ProductInterface as FeedProductInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\TypePoolInterface as SectionTypePoolInterface;
use ShoppingFeed\Manager\Model\Feed\Product\FilterFactory as ProductFilterFactory;
use ShoppingFeed\Manager\Model\Feed\Product\Section\FilterFactory as SectionFilterFactory;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Type\Stock as StockSectionType;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Refresher as FeedRefresher;


class AfterStockItemSaveObserver implements ObserverInterface
{
    const EVENT_KEY_STOCK_ITEM = 'item';

    /**
     * @var FeedRefresher
     */
    private $feedRefresher;

    /**
     * @var SectionTypePoolInterface
     */
    private $sectionTypePool;

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
     * @param SectionTypePoolInterface $sectionTypePool
     * @param ProductFilterFactory $productFilterFactory
     * @param SectionFilterFactory $sectionFilterFactory
     */
    public function __construct(
        FeedRefresher $feedRefresher,
        SectionTypePoolInterface $sectionTypePool,
        ProductFilterFactory $productFilterFactory,
        SectionFilterFactory $sectionFilterFactory
    ) {
        $this->feedRefresher = $feedRefresher;
        $this->sectionTypePool = $sectionTypePool;
        $this->productFilterFactory = $productFilterFactory;
        $this->sectionFilterFactory = $sectionFilterFactory;
    }

    public function execute(Observer $observer)
    {
        if (($stockItem = $observer->getEvent()->getData(static::EVENT_KEY_STOCK_ITEM))
            && ($stockItem instanceof StockItem)
        ) {
            $productFilter = $this->productFilterFactory->create();
            $sectionFilter = $this->sectionFilterFactory->create();
            $productFilter->setProductIds([ $stockItem->getProductId() ]);

            try {
                $sectionFilter->setTypeIds([ $this->sectionTypePool->getTypeByCode(StockSectionType::CODE)->getId() ]);

                $this->feedRefresher->forceProductSectionRefresh(
                    FeedProductInterface::REFRESH_STATE_REQUIRED,
                    $sectionFilter,
                    $productFilter
                );
            } catch (\Exception $e) {
                // Forcing a feed refresh should not prevent from saving the stock item.
            }
        }
    }
}
