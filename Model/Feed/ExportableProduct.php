<?php

namespace ShoppingFeed\Manager\Model\Feed;

use ShoppingFeed\Manager\Api\Data\Feed\ProductInterface as FeedProductInterface;

class ExportableProduct
{
    const TYPE_INDEPENDENT = 'independent';
    const TYPE_PARENT = 'parent';
    const TYPE_BUNDLE = 'bundle';
    const TYPE_CHILD = 'child';

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $type = self::TYPE_INDEPENDENT;

    /**
     * @var int
     */
    private $exportState;

    /**
     * @var array[]
     */
    private $sectionsData = [];

    /**
     * @var ExportableProduct[]
     */
    private $children = [];

    /**
     * @var string[]
     */
    private $configurableAttributeCodes = [];

    /**
     * @var int
     */
    private $bundledQuantity = 1;

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
    public function getExportState()
    {
        return $this->exportState;
    }

    /**
     * @return array[]
     */
    public function getSectionsData()
    {
        return $this->sectionsData;
    }

    /**
     * @return array
     */
    public function getMergedSectionsData()
    {
        return call_user_func_array('array_merge', $this->sectionsData);
    }

    /**
     * @param int $sectionTypeId
     * @return array|null
     */
    public function getSectionTypeData($sectionTypeId)
    {
        return $this->sectionsData[$sectionTypeId] ?? null;
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        return !empty($this->children);
    }

    /**
     * @return ExportableProduct[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @return int[]
     */
    public function getChildrenIds()
    {
        $childrenIds = [];

        foreach ($this->children as $child) {
            $childrenIds[] = $child->getId();
        }

        return array_filter($childrenIds);
    }

    /**
     * @param int $sectionTypeId
     * @return array
     */
    public function getChildrenSectionTypeData($sectionTypeId)
    {
        $childrenData = [];

        foreach ($this->children as $child) {
            $childrenData[] = $child->getSectionTypeData($sectionTypeId);
        }

        return array_filter($childrenData, 'is_array');
    }

    /**
     * @return int[]
     */
    public function getChildrenBundledQuantities()
    {
        $childrenQuantities = [];

        foreach ($this->children as $child) {
            $childrenQuantities[] = $child->getBundledQuantity();
        }

        return $childrenQuantities;
    }

    /**
     * @return string[]
     */
    public function getConfigurableAttributeCodes()
    {
        return $this->configurableAttributeCodes;
    }

    /**
     * @return int
     */
    public function getBundledQuantity()
    {
        return $this->bundledQuantity;
    }

    /**
     * @return bool
     */
    public function isExported()
    {
        return in_array(
            $this->getExportState(),
            [
                FeedProductInterface::STATE_EXPORTED,
                FeedProductInterface::STATE_RETAINED,
            ],
            true
        );
    }

    /**
     * @return bool
     */
    public function isRetained()
    {
        return ($this->getExportState() === FeedProductInterface::STATE_RETAINED);
    }

    /**
     * @return bool
     */
    public function isNonExportable()
    {
        return ($this->getExportState() !== FeedProductInterface::STATE_EXPORTED);
    }

    /**
     * @return bool
     */
    public function isParent()
    {
        return (self::TYPE_PARENT === $this->type);
    }

    /**
     * @return bool
     */
    public function isBundle()
    {
        return (self::TYPE_BUNDLE === $this->type);
    }

    /**
     * @return bool
     */
    public function isChild()
    {
        return (self::TYPE_CHILD === $this->type);
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param int $exportState
     * @return $this
     */
    public function setExportState($exportState)
    {
        $this->exportState = $exportState;
        return $this;
    }

    /**
     * @param array[] $sectionsData
     * @return $this
     */
    public function setSectionsData(array $sectionsData)
    {
        $this->sectionsData = $sectionsData;
        return $this;
    }

    /**
     * @param int $sectionTypeId
     * @param array $sectionData
     * @return $this
     */
    public function setSectionTypeData($sectionTypeId, array $sectionData)
    {
        $this->sectionsData[$sectionTypeId] = $sectionData;
        return $this;
    }

    /**
     * @param array $children
     * @return $this
     */
    public function setChildren(array $children)
    {
        $this->children = $children;
        return $this;
    }

    /**
     * @param string[] $attributeCodes
     * @return $this
     */
    public function setConfigurableAttributeCodes(array $attributeCodes)
    {
        $this->configurableAttributeCodes = $attributeCodes;
        return $this;
    }

    /**
     * @param int $bundledQuantity
     * @return $this
     */
    public function setBundledQuantity($bundledQuantity)
    {
        $this->bundledQuantity = max(1, (int) $bundledQuantity);
        return $this;
    }
}
