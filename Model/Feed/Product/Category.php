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
     * @var int
     */
    private $position;

    /**
     * @var int
     */
    private $globalPosition;

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
        $this->position = (int) $catalogCategory->getPosition();
        $this->globalPosition = $this->position;
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
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @return int
     */
    public function getGlobalPosition()
    {
        return $this->globalPosition;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->isActive;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = (string) $name;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = (string) $url;
    }

    /**
     * @param int $level
     */
    public function setLevel($level)
    {
        $this->level = (int) $level;
    }

    /**
     * @param int $position
     */
    public function setPosition($position)
    {
        $this->position = (int) $position;
    }

    /**
     * @param int $globalPosition
     */
    public function setGlobalPosition($globalPosition)
    {
        $this->globalPosition = (int) $globalPosition;
    }

    /**
     * @param bool $isActive
     */
    public function setIsActive($isActive)
    {
        $this->isActive = (bool) $isActive;
    }
}
