<?php

namespace ShoppingFeed\Manager\Model\Feed\Product;

use ShoppingFeed\Manager\Api\Data\Feed\ProductInterface;
use ShoppingFeed\Manager\Model\AbstractFilter;
use ShoppingFeed\Manager\Model\TimeFilter;

class SectionFilter extends AbstractFilter
{
    /**
     * @var int[]|null
     */
    private $typeIds = null;

    /**
     * @var int[]|null
     */
    private $productIds = null;

    /**
     * @var int[]|null
     */
    private $storeIds = null;

    /**
     * @var int[]|null
     */
    private $refreshStates = null;

    /**
     * @var TimeFilter|null
     */
    private $lastRefreshTimeFilter = null;

    /**
     * @return int[]|null
     */
    public function getTypeIds()
    {
        return $this->typeIds;
    }

    /**
     * @return int[]|null
     */
    public function getProductIds()
    {
        return $this->productIds;
    }

    /**
     * @return int[]|null
     */
    public function getStoreIds()
    {
        return $this->storeIds;
    }

    /**
     * @return int[]|null
     */
    public function getRefreshStates()
    {
        return $this->refreshStates;
    }

    /**
     * @return TimeFilter|null
     */
    public function getLastRefreshTimeFilter()
    {
        return $this->lastRefreshTimeFilter;
    }

    /**
     * @param int[] $typeIds
     * @return $this
     */
    public function setTypeIds(array $typeIds)
    {
        $this->typeIds = $typeIds;
        return $this;
    }

    /**
     * @return $this
     */
    public function unsetTypeIds()
    {
        $this->typeIds = null;
        return $this;
    }

    /**
     * @param int[] $productIds
     * @return $this
     */
    public function setProductIds(array $productIds)
    {
        $this->productIds = $productIds;
        return $this;
    }

    /**
     * @return $this
     */
    public function unsetProductIds()
    {
        $this->productIds = null;
        return $this;
    }

    /**
     * @param int[] $storeIds
     * @return $this
     */
    public function setStoreIds(array $storeIds)
    {
        $this->storeIds = $storeIds;
        return $this;
    }

    /**
     * @return $this
     */
    public function unsetStoreIds()
    {
        $this->storeIds = null;
        return $this;
    }

    /**
     * @param int[] $refreshStates
     * @return $this
     */
    public function setRefreshStates(array $refreshStates)
    {
        $this->refreshStates = array_filter(array_map('intval', $refreshStates));

        if (
            empty($this->refreshStates)
            || empty(array_diff(ProductInterface::ALL_REFRESH_STATES, $this->refreshStates))
        ) {
            $this->refreshStates = null;
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function unsetRefreshStates()
    {
        $this->refreshStates = null;
        return $this;
    }

    /**
     * @param TimeFilter $lastRefreshTimeFilter
     * @return $this
     */
    public function setLastRefreshTimeFilter(TimeFilter $lastRefreshTimeFilter)
    {
        $this->lastRefreshTimeFilter = $lastRefreshTimeFilter;
        return $this;
    }

    /**
     * @return $this
     */
    public function unsetLastRefreshTimeFilter()
    {
        $this->lastRefreshTimeFilter = null;
        return $this;
    }

    public function isEmpty()
    {
        return (
            (null === $this->typeIds)
            && (null === $this->productIds)
            && (null === $this->storeIds)
            && (null === $this->refreshStates)
            && (null === $this->lastRefreshTimeFilter)
        );
    }
}
