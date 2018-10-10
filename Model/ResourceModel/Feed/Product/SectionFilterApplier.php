<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Feed\Product;

use ShoppingFeed\Manager\Model\AbstractFilter;
use ShoppingFeed\Manager\Model\Feed\Product\SectionFilter;
use ShoppingFeed\Manager\Model\ResourceModel\AbstractFilterApplier;
use ShoppingFeed\Manager\Model\TimeFilter;

class SectionFilterApplier extends AbstractFilterApplier
{
    protected function _construct()
    {
        $this->_init('sfm_feed_product_section', 'section_id');
    }

    /**
     * @param SectionFilter $sectionFilter
     * @param string|null $sectionTableAlias
     * @return string[]
     */
    public function getFilterConditions(AbstractFilter $sectionFilter, $sectionTableAlias = null)
    {
        $conditions = [];

        if (is_array($typeIds = $sectionFilter->getTypeIds())) {
            $conditions[] = $this->getQuotedCondition('type_id', 'IN (?)', $typeIds, $sectionTableAlias);
        }

        if (is_array($productIds = $sectionFilter->getProductIds())) {
            $conditions[] = $this->getQuotedCondition('product_id', 'IN (?)', $productIds, $sectionTableAlias);
        }

        if (is_array($storeIds = $sectionFilter->getStoreIds())) {
            $conditions[] = $this->getQuotedCondition('store_id', 'IN (?)', $storeIds, $sectionTableAlias);
        }

        if (is_array($refreshStates = $sectionFilter->getRefreshStates())) {
            $conditions[] = $this->getQuotedCondition('refresh_state', 'IN (?)', $refreshStates, $sectionTableAlias);
        }

        if ($lastRefreshFilter = $sectionFilter->getLastRefreshTimeFilter()) {
            $seconds = $lastRefreshFilter->getSeconds();
            $operator = ($lastRefreshFilter->getMode() === TimeFilter::MODE_BEFORE) ? '<= ?' : '>= ?';

            $conditions[] =
                '('
                . $this->getQuotedCondition(
                    'refreshed_at',
                    'IS NULL',
                    null,
                    $sectionTableAlias
                )
                . ' OR '
                . $this->getQuotedCondition(
                    'refreshed_at',
                    $operator,
                    $this->timeHelper->utcPastDate($seconds),
                    $sectionTableAlias
                )
                . ')';
        }

        return $conditions;
    }
}
