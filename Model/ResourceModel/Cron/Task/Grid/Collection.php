<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Cron\Task\Grid;

use Magento\Framework\Api\Search\AggregationInterface as SearchAggregationInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document as UiDocument;
use ShoppingFeed\Manager\Model\ResourceModel\Cron\Task as TaskResource;
use ShoppingFeed\Manager\Model\ResourceModel\Cron\Task\Collection as TaskCollection;

class Collection extends TaskCollection implements SearchResultInterface
{
    /**
     * @var SearchAggregationInterface
     */
    protected $aggregations;

    protected function _construct()
    {
        $this->_init(UiDocument::class, TaskResource::class);
    }

    public function setItems(?array $items = null)
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
