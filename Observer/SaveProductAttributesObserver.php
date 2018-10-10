<?php

namespace ShoppingFeed\Manager\Observer;

use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\ProductFactory as FeedProductResourceFactory;
use ShoppingFeed\Manager\Ui\DataProvider\Catalog\Product\Form\Modifier\FeedAttributes as UiAttributesModifier;

class SaveProductAttributesObserver implements ObserverInterface
{
    const EVENT_KEY_PRODUCT = 'product';

    /**
     * @var FeedProductResourceFactory
     */
    private $feedProductResourceFactory;

    /**
     * @param FeedProductResourceFactory $feedProductResourceFactory
     */
    public function __construct(FeedProductResourceFactory $feedProductResourceFactory)
    {
        $this->feedProductResourceFactory = $feedProductResourceFactory;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if (($catalogProduct = $observer->getEvent()->getData(static::EVENT_KEY_PRODUCT))
            && ($catalogProduct instanceof CatalogProduct)
            && is_array($feedAttributes = $catalogProduct->getData(UiAttributesModifier::DATA_SCOPE_SFM_MODULE))
        ) {
            $feedProductResource = $this->feedProductResourceFactory->create();

            foreach ($feedAttributes as $storeId => $storeFeedAttributes) {
                $feedProductResource->updateProductFeedAttributes(
                    (int) $catalogProduct->getId(),
                    (int) $storeId,
                    $storeFeedAttributes['is_selected'] ?? false,
                    $storeFeedAttributes['selected_category_id'] ?? null
                );
            }
        }
    }
}
