<?php

namespace ShoppingFeed\Manager\Plugin\Sales\Order\Grid;

use Magento\Framework\Api\Filter;
use Magento\Framework\Data\Collection;
use Magento\Framework\DB\Select as DbSelect;
use Magento\Framework\View\Element\UiComponent\DataProvider\FulltextFilter;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OrderGridCollection;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;

class FulltextFilterPlugin
{
    /**
     * @return string[]
     */
    public function getFilterableMarketplaceOrderFieldNames()
    {
        return [
            MarketplaceOrderInterface::MARKETPLACE_ORDER_NUMBER,
            MarketplaceOrderInterface::MARKETPLACE_NAME,
        ];
    }

    /**
     * @param FulltextFilter $subject
     * @param callable $proceed
     * @param Collection $collection
     * @param Filter $filter
     * @return mixed
     * @throws \Zend_Db_Select_Exception
     */
    public function aroundApply(FulltextFilter $subject, callable $proceed, Collection $collection, Filter $filter)
    {
        $result = $proceed($collection, $filter);

        if ($collection instanceof OrderGridCollection) {
            // We search for a single MATCH AGAINST construct. If it exists, it must correspond to the keyword filter.
            $select = $collection->getSelect();
            $whereClauses = $select->getPart(DbSelect::WHERE);
            $fulltextClauseIndex = null;

            foreach ($whereClauses as $index => $whereClause) {
                /** @see FulltextFilter::apply() */
                if (
                    (false !== strpos($whereClause, 'MATCH('))
                    && (false !== strpos($whereClause, 'AGAINST('))
                ) {
                    if (null === $fulltextClauseIndex) {
                        $fulltextClauseIndex = $index;
                    } else {
                        $fulltextClauseIndex = null;
                        break;
                    }
                }
            }

            if (null !== $fulltextClauseIndex) {
                $filterableFieldNames = [
                    'sfm_marketplace_name',
                    'sfm_marketplace_order_number',
                ];

                $filterFields = [];
                $filterConditions = [];

                foreach ($filterableFieldNames as $fieldName) {
                    $filterFields[$fieldName] = $fieldName;
                    $filterConditions[$fieldName] = [ 'like' => '%' . $filter->getValue() . '%' ];
                }

                $select->reset(DbSelect::WHERE);
                $collection->addFieldToFilter($filterFields, $filterConditions);

                $whereClauses[$fulltextClauseIndex] =
                    '((' . $whereClauses[$fulltextClauseIndex] . ')'
                    . DbSelect::SQL_OR
                    . '(' . implode(' ', $select->getPart(DbSelect::WHERE)) . '))';

                $select->setPart(DbSelect::WHERE, $whereClauses);
            }
        }

        return $result;
    }
}
