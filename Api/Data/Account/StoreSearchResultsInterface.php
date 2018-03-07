<?php

namespace ShoppingFeed\Manager\Api\Data\Account;

use Magento\Framework\Api\SearchResultsInterface;


/**
 * @api
 */
interface StoreSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return StoreInterface[]
     */
    public function getItems();

    /**
     * @param StoreInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
