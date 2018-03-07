<?php

namespace ShoppingFeed\Manager\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;


/**
 * @api
 */
interface AccountSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return AccountInterface[]
     */
    public function getItems();

    /**
     * @param AccountInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
