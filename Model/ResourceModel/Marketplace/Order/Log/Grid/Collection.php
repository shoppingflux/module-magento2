<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Log\Grid;

use Magento\Framework\Api\Search\AggregationInterface as SearchAggregationInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document as UiDocument;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Log as LogResource;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Log\Collection as LogCollection;

class Collection extends LogCollection implements SearchResultInterface
{
    const FIELD_SHOPPING_FEED_ACCOUNT_NAME = 'shopping_feed_account_name';

    /**
     * @var SearchAggregationInterface
     */
    protected $aggregations;

    protected function _construct()
    {
        $this->_init(UiDocument::class, LogResource::class);
    }

    protected function _initSelect()
    {
        parent::_initSelect();

        $this->getSelect()
            ->joinInner(
                [ 'order_table' => $this->tableDictionary->getMarketplaceOrderTableName() ],
                'main_table.order_id = order_table.order_id',
                [ 'marketplace_name', 'marketplace_order_number' ]
            )
            ->joinInner(
                [ 'store_table' => $this->tableDictionary->getAccountStoreTableName() ],
                'order_table.store_id = store_table.store_id',
                [ static::FIELD_SHOPPING_FEED_ACCOUNT_NAME => 'store_table.shopping_feed_name' ]
            );

        return $this;
    }

    public function setItems(array $items = null)
    {
        return $this;
    }

    public function getAggregations()
    {
        return $this->aggregations;
    }

    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;
        return $this;
    }

    public function getSearchCriteria()
    {
        return null;
    }

    public function setSearchCriteria(SearchCriteriaInterface $searchCriteria)
    {
        return $this;
    }

    public function getTotalCount()
    {
        return $this->getSize();
    }

    public function setTotalCount($totalCount)
    {
        return $this;
    }
}
