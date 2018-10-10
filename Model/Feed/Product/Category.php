<?php

namespace ShoppingFeed\Manager\Model\Feed\Product;

use Magento\Catalog\Model\Category as CatalogCategory;

class Category
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $parentId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $url;

    /**
     * @var int
     */
    private $level;

    /**
     * @var bool
     */
    private $isActive;

    /**
     * @param CatalogCategory $catalogCategory
     */
    public function __construct(CatalogCategory $catalogCategory)
    {
        $this->id = (int) $catalogCategory->getId();
        $this->parentId = (int) $catalogCategory->getParentId();
        $this->name = trim($catalogCategory->getName());
        $this->url = $catalogCategory->getUrl();
        $this->level = (int) $catalogCategory->getLevel();
        $this->isActive = (bool) $catalogCategory->getIsActive();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->isActive;
    }
}
