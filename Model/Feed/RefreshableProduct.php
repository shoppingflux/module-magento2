<?php

namespace ShoppingFeed\Manager\Model\Feed;

use Magento\Catalog\Model\Product as CatalogProduct;
use ShoppingFeed\Manager\Model\Feed\Product as FeedProduct;

class RefreshableProduct
{
    /**
     * @var FeedProduct|null
     */
    private $feedProduct = null;

    /**
     * @var CatalogProduct|null
     */
    private $catalogProduct = null;

    /**
     * @var bool
     */
    private $hasLoadedCatalogProduct = false;

    /**
     * @var bool
     */
    private $hasRefreshableExportState = false;

    /**
     * @var int[]
     */
    private $refreshableSectionTypeIds = [];

    /**
     * @return int
     */
    public function getId()
    {
        return (null !== $this->feedProduct) ? $this->feedProduct->getId() : null;
    }

    /**
     * @return FeedProduct|null
     */
    public function getFeedProduct()
    {
        return $this->feedProduct;
    }

    /**
     * @return CatalogProduct|null
     */
    public function getCatalogProduct()
    {
        return $this->catalogProduct;
    }

    /**
     * @return bool
     */
    public function hasLoadedCatalogProduct()
    {
        return $this->hasLoadedCatalogProduct;
    }

    /**
     * @return bool
     */
    public function hasRefreshableExportState()
    {
        return $this->hasRefreshableExportState;
    }

    /**
     * @param int $sectionTypeId
     * @return bool
     */
    public function hasRefreshableSectionType($sectionTypeId)
    {
        return in_array($sectionTypeId, $this->refreshableSectionTypeIds, true);
    }

    /**
     * @return int[]
     */
    public function getRefreshableSectionTypeIds()
    {
        return $this->refreshableSectionTypeIds;
    }

    /**
     * @param FeedProduct $feedProduct
     * @return $this
     */
    public function setFeedProduct(FeedProduct $feedProduct)
    {
        $this->feedProduct = $feedProduct;
        return $this;
    }

    /**
     * @param CatalogProduct $catalogProduct
     * @param bool $isLoadedProduct
     * @return $this
     */
    public function setCatalogProduct(CatalogProduct $catalogProduct, $isLoadedProduct)
    {
        $this->catalogProduct = $catalogProduct;
        $this->hasLoadedCatalogProduct = (bool) $isLoadedProduct;
        return $this;
    }

    /**
     * @param bool $flag
     * @return $this
     */
    public function setHasRefreshableExportState($flag)
    {
        $this->hasRefreshableExportState = (bool) $flag;
        return $this;
    }

    /**
     * @param int[] $refreshableSectionTypeIds
     * @return $this
     */
    public function setRefreshableSectionTypeIds(array $refreshableSectionTypeIds)
    {
        $this->refreshableSectionTypeIds = $refreshableSectionTypeIds;
        return $this;
    }
}
