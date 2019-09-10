<?php

namespace ShoppingFeed\Manager\Observer;

use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use ShoppingFeed\Manager\Model\Feed\RealTimeUpdater as RealTimeFeedUpdater;

class AfterProductSaveObserver implements ObserverInterface
{
    const EVENT_KEY_PRODUCT = 'product';

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
        if (($catalogProduct = $observer->getEvent()->getData(static::EVENT_KEY_PRODUCT))
            && ($catalogProduct instanceof CatalogProduct)
            && ($productId = (int) $catalogProduct->getId())
        ) {
            try {
                $this->realTimeFeedUpdater->handleCatalogProductSave($catalogProduct);
            } catch (\Exception $e) {
                // Do not prevent from saving the product.
            }
        }
    }
}
