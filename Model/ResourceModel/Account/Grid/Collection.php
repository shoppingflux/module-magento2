<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Account\Grid;

use Magento\Framework\Api\Search\AggregationInterface as SearchAggregationInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document as UiDocument;
use ShoppingFeed\Manager\Model\ResourceModel\Account as AccountResource;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Collection as AccountCollection;


class Collection extends AccountCollection implements SearchResultInterface
{
    /**
     * @var SearchAggregationInterface
     */
    protected $aggregations;

    protected function _construct()
    {
        $this->_init(UiDocument::class, AccountResource::class);
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
