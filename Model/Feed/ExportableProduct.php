<?php

namespace ShoppingFeed\Manager\Model\Feed;

class ExportableProduct
{
    /**
     * @var int
     */
    private $id;

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
     * @return string[]
     */
    public function getConfigurableAttributeCodes()
    {
        return $this->configurableAttributeCodes;
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
     * @param string[] $configurableAttributeCodes
     * @return $this
     */
    public function setChildren(array $children, array $configurableAttributeCodes = [])
    {
        $this->children = $children;
        $this->configurableAttributeCodes = $configurableAttributeCodes;
        return $this;
    }
}
