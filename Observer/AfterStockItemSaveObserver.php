<?php

namespace ShoppingFeed\Manager\Observer;

use Magento\CatalogInventory\Model\Stock\Item as StockItem;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use ShoppingFeed\Manager\Model\Feed\RealTimeUpdater as RealTimeFeedUpdater;

class AfterStockItemSaveObserver implements ObserverInterface
{
    const EVENT_KEY_STOCK_ITEM = 'item';

    /**
     * @var RealTimeFeedUpdater
     */
    private $realTimeFeedUpdater;

    /**
     * @param RealTimeFeedUpdater $realTimeFeedUpdater
     */
    public function __construct(RealTimeFeedUpdater $realTimeFeedUpdater)
    {
        $this->realTimeFeedUpdater = $realTimeFeedUpdater;
    }

    public function execute(Observer $observer)
    {
        if (($stockItem = $observer->getEvent()->getData(static::EVENT_KEY_STOCK_ITEM))
            && ($stockItem instanceof StockItem)
        ) {
            try {
                $this->realTimeFeedUpdater->handleStockItemSave($stockItem);
            } catch (\Exception $e) {
                // Do not prevent from saving the stock item.
            }
        }
    }
}
