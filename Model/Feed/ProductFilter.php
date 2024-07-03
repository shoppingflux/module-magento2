<?php

namespace ShoppingFeed\Manager\Model\Feed;

use ShoppingFeed\Manager\Model\AbstractFilter;
use ShoppingFeed\Manager\Model\TimeFilter;

class ProductFilter extends AbstractFilter
{
    /**
     * @var int[]|null
     */
    private $productIds = null;

    /**
     * @var int[]|null
     */
    private $storeIds = null;

    /**
     * @var bool
     */
    private $selectedOnly = false;

    /**
     * @var int[]|null
     */
    private $exportStates = null;

    /**
     * @var int[]|null
     */
    private $exportStateRefreshStates = null;

    /**
     * @var TimeFilter|null
     */
    private $lastExportStateRefreshTimeFilter = null;

    /**
     * @var TimeFilter|null
     */
    private $exportRetentionStartTimeFilter = null;

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
     * @return bool
     */
    public function isSelectedOnly()
    {
        return $this->selectedOnly;
    }

    /**
     * @return int[]|null
     */
    public function getExportStates()
    {
        return $this->exportStates;
    }

    /**
     * @return int[]|null
     */
    public function getExportStateRefreshStates()
    {
        return $this->exportStateRefreshStates;
    }

    /**
     * @return TimeFilter|null
     */
    public function getLastExportStateRefreshTimeFilter()
    {
        return $this->lastExportStateRefreshTimeFilter;
    }

    /**
     * @return TimeFilter|null
     */
    public function getExportRetentionStartTimeFilter()
    {
        return $this->exportRetentionStartTimeFilter;
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
     * @param bool $selectedOnly
     * @return $this
     */
    public function setSelectedOnly($selectedOnly)
    {
        $this->selectedOnly = (bool) $selectedOnly;
        return $this;
    }

    /**
     * @param int[] $exportStates
     * @return $this
     */
    public function setExportStates(array $exportStates)
    {
        $this->exportStates = $exportStates;
        return $this;
    }

    /**
     * @return $this
     */
    public function unsetExportStates()
    {
        $this->exportStates = null;
        return $this;
    }

    /**
     * @param int[] $exportStateRefreshStates
     * @return $this
     */
    public function setExportStateRefreshStates(array $exportStateRefreshStates)
    {
        $this->exportStateRefreshStates = $exportStateRefreshStates;
        return $this;
    }

    /**
     * @return $this
     */
    public function unsetExportStateRefreshStates()
    {
        $this->exportStateRefreshStates = null;
        return $this;
    }

    /**
     * @param TimeFilter $lastExportStateRefreshTimeFilter
     * @return $this
     */
    public function setLastExportStateRefreshTimeFilter(TimeFilter $lastExportStateRefreshTimeFilter)
    {
        $this->lastExportStateRefreshTimeFilter = $lastExportStateRefreshTimeFilter;
        return $this;
    }

    /**
     * @return $this
     */
    public function unsetLastExportStateRefreshTimeFilter()
    {
        $this->lastExportStateRefreshTimeFilter = null;
        return $this;
    }

    /**
     * @param TimeFilter $exportRetentionStartTimeFilter
     * @return $this
     */
    public function setExportRetentionStartTimeFilter(TimeFilter $exportRetentionStartTimeFilter)
    {
        $this->exportRetentionStartTimeFilter = $exportRetentionStartTimeFilter;
        return $this;
    }

    /**
     * @return $this
     */
    public function unsetExportRetentionStartTimeFilter()
    {
        $this->exportRetentionStartTimeFilter = null;
        return $this;
    }

    public function isEmpty()
    {
        return (
            (null === $this->productIds)
            && (null === $this->storeIds)
            && (false === $this->selectedOnly)
            && (null === $this->exportStates)
            && (null === $this->exportStateRefreshStates)
            && (null === $this->lastExportStateRefreshTimeFilter)
            && (null === $this->exportRetentionStartTimeFilter)
        );
    }

    // @todo also have lastExportStateRefreshStateUpdateTimeFilter
}
