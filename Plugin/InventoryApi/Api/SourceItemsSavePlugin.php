<?php

namespace ShoppingFeed\Manager\Plugin\InventoryApi\Api;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use ShoppingFeed\Manager\Model\Feed\RealTimeUpdater as RealTimeFeedUpdater;

class SourceItemsSavePlugin
{
    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var RealTimeFeedUpdater
     */
    private $realTimeFeedUpdater;

    /**
     * @param ProductCollectionFactory $productCollectionFactory
     */
    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        RealTimeFeedUpdater $realTimeFeedUpdater
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->realTimeFeedUpdater = $realTimeFeedUpdater;
    }

    /**
     * @param SourceItemsSaveInterface $subject
     * @param null $result
     * @param array $sourceItems
     */
    public function afterExecute(SourceItemsSaveInterface $subject, $result, array $sourceItems)
    {
        $productSkus = [];

        foreach ($sourceItems as $sourceItem) {
            if ($sourceItem instanceof SourceItemInterface) {
                $productSkus[] = $sourceItem->getSku();
            }
        }

        if (!empty($productSkus)) {
            $productCollection = $this->productCollectionFactory->create();
            $productCollection->addAttributeToFilter('sku', [ 'in' => $productSkus ]);

            $productIds = $productCollection->getAllIds();

            if (!empty($productIds)) {
                $this->realTimeFeedUpdater->handleProductsQuantityChange($productIds);
            }
        }
    }
}
