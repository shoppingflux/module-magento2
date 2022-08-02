<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Grid;

use Magento\Framework\Api\Search\AggregationInterface as SearchAggregationInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document as UiDocument;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order as OrderResource;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Collection as OrderCollection;

class Collection extends OrderCollection implements SearchResultInterface
{
    const FIELD_SHOPPING_FEED_ACCOUNT_NAME = 'shopping_feed_account_name';
    const FIELD_IS_IMPORTED = 'imported';

    const IS_IMPORTED_FILTER_VALUE_UNIMPORTED = '_unimported_';

    /**
     * @var SearchAggregationInterface
     */
    protected $aggregations;

    protected function _construct()
    {
        $this->_init(UiDocument::class, OrderResource::class);
    }

    protected function _initSelect()
    {
        parent::_initSelect();

        $this->getSelect()
            ->joinInner(
                [ 'store_table' => $this->tableDictionary->getAccountStoreTableName() ],
                'main_table.store_id = store_table.store_id',
                [
                    static::FIELD_IS_IMPORTED => '(main_table.sales_order_id > 0)',
                    static::FIELD_SHOPPING_FEED_ACCOUNT_NAME => 'store_table.shopping_feed_name',
                ]
            );

        return $this;
    }

    public function addFieldToFilter($field, $condition = null)
    {
        if (
            (static::FIELD_IS_IMPORTED === $field)
            && is_array($condition)
            && isset($condition['eq'])
        ) {
            if ($condition['eq'] === static::IS_IMPORTED_FILTER_VALUE_UNIMPORTED) {
                $condition['eq'] = 0;
            }

            return parent::addFieldToFilter(
                OrderInterface::SALES_ORDER_ID,
                $condition['eq'] ? [ 'gt' => 0 ] : [ 'null' => true ]
            );
        } elseif (
            (OrderInterface::ORDER_ID === $field)
            && is_array($condition)
            && (strpos($condition['eq'] ?? '', '_') !== false)
        ) {
            return parent::addFieldToFilter(
                OrderInterface::ORDER_ID,
                [ 'in' => array_map('intval', explode('_', $condition['eq'])) ]
            );
        }

        return parent::addFieldToFilter($field, $condition);
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
