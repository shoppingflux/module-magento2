<?php

namespace ShoppingFeed\Manager\Model\Feed\Product;

use ShoppingFeed\Manager\Model\AbstractFilter;
use ShoppingFeed\Manager\Model\Time\Filter as TimeFilter;


class Filter extends AbstractFilter
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
     * @param int[] $productIds
     * @return $this
     */
    public function setProductIds(array $productIds)
    {
        $this->productIds = $productIds;
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
     * @param int[] $exportStateRefreshStates
     * @return $this
     */
    public function setExportStateRefreshStates(array $exportStateRefreshStates)
    {
        $this->exportStateRefreshStates = $exportStateRefreshStates;
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

    // @todo also have lastExportStateRefreshStateUpdateTimeFilter
}
