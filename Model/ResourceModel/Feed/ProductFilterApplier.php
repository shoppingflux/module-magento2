<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Feed;

use ShoppingFeed\Manager\Api\Data\Feed\ProductInterface;
use ShoppingFeed\Manager\Model\AbstractFilter;
use ShoppingFeed\Manager\Model\Feed\ProductFilter;
use ShoppingFeed\Manager\Model\ResourceModel\AbstractFilterApplier;
use ShoppingFeed\Manager\Model\TimeFilter;

class ProductFilterApplier extends AbstractFilterApplier
{
    protected function _construct()
    {
        $this->_init('sfm_feed_product', 'product_id');
    }

    /**
     * @param ProductFilter $productFilter
     * @param string|null $productTableAlias
     * @return string[]
     */
    public function getFilterConditions(AbstractFilter $productFilter, $productTableAlias = null)
    {
        $conditions = [];

        if (is_array($productIds = $productFilter->getProductIds())) {
            $conditions[] = $this->getQuotedCondition('product_id', 'IN (?)', $productIds, $productTableAlias);
        }

        if (is_array($storeIds = $productFilter->getStoreIds())) {
            $conditions[] = $this->getQuotedCondition('store_id', 'IN (?)', $storeIds, $productTableAlias);
        }

        if ($productFilter->isSelectedOnly()) {
            $conditions[] = $this->getQuotedCondition('is_selected', '= ?', true, $productTableAlias);
        }

        if (is_array($exportStates = $productFilter->getExportStates())) {
            $conditions[] = $this->getQuotedCondition('export_state', 'IN (?)', $exportStates, $productTableAlias);
        }

        if (is_array($childExportStates = $productFilter->getChildExportStates())) {
            $conditions[] = $this->getQuotedCondition(
                'child_export_state',
                'IN (?)',
                $childExportStates,
                $productTableAlias
            );
        }

        if (is_array($refreshStates = $productFilter->getExportStateRefreshStates())) {
            $conditions[] = $this->getQuotedCondition(
                'export_state_refresh_state',
                'IN (?)',
                $refreshStates,
                $productTableAlias
            );
        }

        if ($lastRefreshFilter = $productFilter->getLastExportStateRefreshTimeFilter()) {
            $seconds = $lastRefreshFilter->getSeconds();
            $operator = ($lastRefreshFilter->getMode() === TimeFilter::MODE_BEFORE) ? '<= ?' : '>= ?';

            $conditions[] =
                '('
                . $this->getQuotedCondition(
                    'export_state_refreshed_at',
                    'IS NULL',
                    null,
                    $productTableAlias
                )
                . ' OR '
                . $this->getQuotedCondition(
                    'export_state_refreshed_at',
                    $operator,
                    $this->timeHelper->utcPastDate($seconds),
                    $productTableAlias
                )
                . ')';
        }

        if ($retentionStartFilter = $productFilter->getExportRetentionStartTimeFilter()) {
            $seconds = $retentionStartFilter->getSeconds();
            $operator = ($retentionStartFilter->getMode() === TimeFilter::MODE_BEFORE) ? '<= ?' : '>= ?';

            $conditions[] =
                '('
                . $this->getQuotedCondition(
                    'export_state',
                    '!= ?',
                    ProductInterface::STATE_RETAINED,
                    $productTableAlias
                )
                . ' OR '
                . $this->getQuotedCondition(
                    'export_retention_started_at',
                    $operator,
                    $this->timeHelper->utcPastDate($seconds),
                    $productTableAlias
                )
                . ')';
        }

        return $conditions;
    }
}
