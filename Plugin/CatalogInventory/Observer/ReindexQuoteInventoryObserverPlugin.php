<?php

namespace ShoppingFeed\Manager\Plugin\CatalogInventory\Observer;

use Magento\CatalogInventory\Observer\ItemsForReindex;
use Magento\CatalogInventory\Observer\ReindexQuoteInventoryObserver;
use Magento\Framework\Event\Observer as EventObserver;

class ReindexQuoteInventoryObserverPlugin
{
    /**
     * @var ItemsForReindex
     */
    private $itemsForReindex;

    /**
     * @param ItemsForReindex $itemsForReindex
     */
    public function __construct(ItemsForReindex $itemsForReindex)
    {
        $this->itemsForReindex = $itemsForReindex;
    }

    /**
     * @param ReindexQuoteInventoryObserver $subject
     * @param EventObserver $observer
     */
    public function beforeExecute(ReindexQuoteInventoryObserver $subject, EventObserver $observer)
    {
        if (!is_array($this->itemsForReindex->getItems())) {
            $this->itemsForReindex->setItems([]);
        }
    }
}
