<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Feed;

use Magento\Bundle\Model\ResourceModel\Selection\CollectionFactory as BundleSelectionCollectionFactory;
use Magento\Framework\DB\Select as DbSelect;
use Magento\Framework\Model\ResourceModel\Db\Context as DbContext;
use ShoppingFeed\Manager\Api\Data\Feed\ProductInterface as FeedProductInterface;
use ShoppingFeed\Manager\Model\Feed\ExportableProduct;
use ShoppingFeed\Manager\Model\Feed\ExportableProductFactory;
use ShoppingFeed\Manager\Model\ResourceModel\AbstractDb;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product\Section as FeedSectionResource;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product\SectionFactory as FeedSectionResourceFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product\SectionFilterApplier;
use ShoppingFeed\Manager\Model\ResourceModel\Query\IteratorFactory as QueryIteratorFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Table\Dictionary as TableDictionary;
use ShoppingFeed\Manager\Model\TimeHelper;
use Zend_Db_Statement_Interface;

class Exporter extends AbstractDb
{
    const BASE_SECTION_DATA_KEY = 'section_%d';

    /**
     * @var QueryIteratorFactory
     */
    private $queryIteratorFactory;

    /**
     * @var BundleSelectionCollectionFactory
     */
    private $bundleSelectionCollectionFactory;

    /**
     * @var FeedSectionResource
     */
    private $feedSectionResource;

    /**
     * @var ExportableProductFactory
     */
    private $exportableProductFactory;

    /**
     * @var bool|null
     */
    private $isCatalogStagingEnabled = null;

    /**
     * @param DbContext $context
     * @param TimeHelper $timeHelper
     * @param TableDictionary $tableDictionary
     * @param ProductFilterApplier $productFilterApplier
     * @param SectionFilterApplier $sectionFilterApplier
     * @param QueryIteratorFactory $queryIteratorFactory
     * @param BundleSelectionCollectionFactory $bundleSelectionCollectionFactory
     * @param FeedSectionResourceFactory $feedSectionResourceFactory
     * @param ExportableProductFactory $exportableProductFactory
     * @param string|null $connectionName
     */
    public function __construct(
        DbContext $context,
        TimeHelper $timeHelper,
        TableDictionary $tableDictionary,
        ProductFilterApplier $productFilterApplier,
        SectionFilterApplier $sectionFilterApplier,
        QueryIteratorFactory $queryIteratorFactory,
        BundleSelectionCollectionFactory $bundleSelectionCollectionFactory,
        FeedSectionResourceFactory $feedSectionResourceFactory,
        ExportableProductFactory $exportableProductFactory,
        $connectionName = null
    ) {
        $this->queryIteratorFactory = $queryIteratorFactory;
        $this->bundleSelectionCollectionFactory = $bundleSelectionCollectionFactory;
        $this->feedSectionResource = $feedSectionResourceFactory->create();
        $this->exportableProductFactory = $exportableProductFactory;

        parent::__construct(
            $context,
            $timeHelper,
            $tableDictionary,
            $productFilterApplier,
            $sectionFilterApplier,
            $connectionName
        );
    }

    protected function _construct()
    {
        $this->_init('sfm_feed_product', 'product_id');
    }

    /**
     * @return bool
     */
    private function isCatalogStagingEnabled()
    {
        if (null === $this->isCatalogStagingEnabled) {
            $this->isCatalogStagingEnabled = $this->getConnection()
                ->tableColumnExists($this->tableDictionary->getCatalogProductTableName(), 'row_id');
        }

        return $this->isCatalogStagingEnabled;
    }

    /**
     * @param int[]|null $productIds
     * @return \Zend_Db_Expr
     */
    private function getConfigurableParentIdsQuery($productIds = null)
    {
        $idsSelect = $this->getConnection()
            ->select()
            ->distinct(true);

        if ($this->isCatalogStagingEnabled()) {
            $idsSelect->from(
                [ 'catalog_product_table' => $this->tableDictionary->getCatalogProductTableName() ],
                [ 'entity_id' ]
            );

            $idsSelect->joinInner(
                [ 'configurable_link_table' => $this->tableDictionary->getConfigurableProductLinkTableName() ],
                'configurable_link_table.parent_id = catalog_product_table.row_id',
                []
            );

            if (is_array($productIds)) {
                $idsSelect->where('entity_id IN (?)', $productIds);
                $idsSelect->orWhere('product_id IN (?)', $productIds);
            }
        } else {
            $idsSelect = $this->getConnection()
                ->select()
                ->from($this->tableDictionary->getConfigurableProductLinkTableName(), [ 'parent_id' ]);

            if (is_array($productIds)) {
                $idsSelect->where('parent_id IN (?)', $productIds);
                $idsSelect->orWhere('product_id IN (?)', $productIds);
            }
        }

        return new \Zend_Db_Expr($idsSelect);
    }

    /**
     * @param int[]|null $productIds
     * @return \Zend_Db_Expr
     */
    private function getConfigurableChildrenIdsQuery($productIds = null)
    {
        $idsSelect = $this->getConnection()
            ->select()
            ->distinct(true)
            ->from($this->tableDictionary->getConfigurableProductLinkTableName(), [ 'product_id' ]);

        if (is_array($productIds)) {
            $idsSelect->where('product_id IN (?)', $productIds);
        }

        return new \Zend_Db_Expr($idsSelect);
    }

    /**
     * @param int[]|null $productIds
     * @return \Zend_Db_Expr
     */
    private function getBundleParentIdsQuery($productIds = null)
    {
        $idsSelect = $this->getConnection()
            ->select()
            ->distinct(true);

        if ($this->isCatalogStagingEnabled()) {
            $idsSelect->from(
                [ 'catalog_product_table' => $this->tableDictionary->getCatalogProductTableName() ],
                [ 'entity_id' ]
            );

            $idsSelect->joinInner(
                [ 'bundle_selection_table' => $this->tableDictionary->getBundleProductSelectionTableName() ],
                'bundle_selection_table.parent_product_id = catalog_product_table.row_id',
                []
            );

            if (is_array($productIds)) {
                $idsSelect->where('catalog_product_table.entity_id IN (?)', $productIds);
                $idsSelect->orWhere('bundle_selection_table.product_id IN (?)', $productIds);
            }
        } else {
            $idsSelect = $this->getConnection()
                ->select()
                ->from(
                    [ 'bundle_selection_table' => $this->tableDictionary->getBundleProductSelectionTableName() ],
                    [ 'parent_product_id' ]
                );

            if (is_array($productIds)) {
                $idsSelect->where('bundle_selection_table.parent_product_id IN (?)', $productIds);
                $idsSelect->orWhere('bundle_selection_table.product_id IN (?)', $productIds);
            }
        }

        return new \Zend_Db_Expr($idsSelect);
    }

    /**
     * @param int $storeId
     * @param int[] $exportStates
     * @param int $retentionDuration
     * @param bool $isChildrenSelect
     * @return DbSelect
     */
    private function getExportableProductBaseSelect(
        $storeId,
        $exportStates,
        $retentionDuration,
        $isChildrenSelect = false
    ) {
        $connection = $this->getConnection();

        $baseSelect = $connection->select()
            ->from([ 'product_table' => $this->tableDictionary->getFeedProductTableName() ], [ 'product_id' ])
            ->where('product_table.store_id = ?', $storeId)
            ->where('export_state_refreshed_at IS NOT NULL');

        if ($isChildrenSelect) {
            $baseSelect->columns([ 'export_state' => 'child_export_state' ]);
            $baseSelect->where('child_export_state IN (?)', $exportStates);
        } else {
            $baseSelect->columns([ 'export_state' ]);
            $baseSelect->where('export_state IN (?)', $exportStates);

            if ($retentionDuration > 0) {
                $baseSelect->where(
                    $connection->quoteInto('(export_state != ?)', FeedProductInterface::STATE_RETAINED)
                    . ' OR '
                    . $connection->quoteInto(
                        '(export_retention_started_at >= ?)',
                        $this->timeHelper->utcPastDate($retentionDuration)
                    )
                );
            }
        }

        return $baseSelect;
    }

    /**
     * @return string[][]
     */
    private function getParentConfigurableAttributeCodes()
    {
        $connection = $this->getConnection();

        $attributeMap = $connection->fetchPairs(
            $connection->select()
                ->from(
                    $this->tableDictionary->getEavAttributeTableName(),
                    [ 'attribute_id', 'attribute_code' ]
                )
        );

        $productAttributeIds = $connection->fetchAll(
            $connection->select()
                ->from(
                    $this->tableDictionary->getConfigurableProductAttributeTableName(),
                    [ 'product_id', 'attribute_id' ]
                )
        );

        $productAttributeCodes = [];

        foreach ($productAttributeIds as $row) {
            $productId = (int) $row['product_id'];
            $attributeId = (int) $row['attribute_id'];

            if (isset($attributeMap[$attributeId])) {
                $productAttributeCodes[$productId][] = $attributeMap[$attributeId];
            }
        }

        return $productAttributeCodes;
    }

    /**
     * @param DbSelect $productSelect
     * @param int[] $sectionTypeIds
     */
    private function joinSectionTablesToProductSelect(
        DbSelect $productSelect,
        array $sectionTypeIds,
        $joinType = DbSelect::INNER_JOIN
    ) {
        $feedSectionTable = $this->tableDictionary->getFeedProductSectionTableName();
        $connection = $this->getConnection();

        foreach ($sectionTypeIds as $sectionTypeId) {
            $sectionTableAlias = sprintf('section_%d_table', $sectionTypeId);
            $sectionDataKey = sprintf(self::BASE_SECTION_DATA_KEY, $sectionTypeId);

            $joinConditions = implode(
                ' AND ',
                [
                    'product_table.product_id = ' . $sectionTableAlias . '.product_id',
                    'product_table.store_id = ' . $sectionTableAlias . '.store_id',
                    $sectionTableAlias . '.refreshed_at IS NOT NULL',
                    $connection->quoteInto($sectionTableAlias . '.type_id = ?', $sectionTypeId),
                ]
            );

            if (DbSelect::INNER_JOIN === $joinType) {
                $productSelect->joinInner(
                    [ $sectionTableAlias => $feedSectionTable ],
                    $joinConditions,
                    [ $sectionDataKey => 'data' ]
                );
            } elseif (DbSelect::LEFT_JOIN === $joinType) {
                $productSelect->joinLeft(
                    [ $sectionTableAlias => $feedSectionTable ],
                    $joinConditions,
                    [ $sectionDataKey => 'data' ]
                );
            } else {
                throw new Exception(sprintf('Unsupported join type: "%s".', $joinType));
            }
        }
    }

    /**
     * @param DbSelect $productSelect
     * @param int[]|null $productIds
     */
    private function joinConfigurableParentIdToChildrenSelect(DbSelect $productSelect, $productIds = null)
    {
        if ($this->isCatalogStagingEnabled()) {
            $productSelect->joinInner(
                [ 'configurable_link_table' => $this->tableDictionary->getConfigurableProductLinkTableName() ],
                'product_table.product_id = configurable_link_table.product_id',
                []
            );

            $productSelect->joinInner(
                [ 'catalog_parent_table' => $this->tableDictionary->getCatalogProductTableName() ],
                'catalog_parent_table.row_id = configurable_link_table.parent_id',
                [ 'parent_id' => 'entity_id' ]
            );

            if (is_array($productIds)) {
                $productSelect->where(
                    'catalog_parent_table.entity_id IN (?)',
                    $this->getConfigurableParentIdsQuery($productIds)
                );
            }
        } else {
            $productSelect->joinInner(
                [ 'configurable_link_table' => $this->tableDictionary->getConfigurableProductLinkTableName() ],
                'product_table.product_id = configurable_link_table.product_id',
                [ 'parent_id' ]
            );

            if (is_array($productIds)) {
                $productSelect->where(
                    'configurable_link_table.parent_id IN (?)',
                    $this->getConfigurableParentIdsQuery($productIds)
                );
            }
        }
    }

    /**
     * @param DbSelect $productSelect
     * @param int[]|null $productIds
     */
    private function joinBundleParentIdToChildrenSelect(DbSelect $productSelect, $productIds = null)
    {
        if ($this->isCatalogStagingEnabled()) {
            $productSelect->joinInner(
                [ 'bundle_option_table' => $this->tableDictionary->getBundleProductOptionTableName() ],
                'bundle_option_table.option_id = selection.option_id',
                []
            );

            $productSelect->joinInner(
                [ 'catalog_parent_table' => $this->tableDictionary->getCatalogProductTableName() ],
                'bundle_option_table.parent_id = catalog_parent_table.row_id',
                [ 'parent_id' => 'entity_id' ]
            );

            if (is_array($productIds)) {
                $productSelect->where(
                    'catalog_parent_table.entity_id IN (?)',
                    $this->getBundleParentIdsQuery($productIds)
                );
            }
        } else {
            $productSelect->joinInner(
                [ 'bundle_option_table' => $this->tableDictionary->getBundleProductOptionTableName() ],
                'bundle_option_table.option_id = selection.option_id',
                [ 'parent_id' ]
            );

            if (is_array($productIds)) {
                $productSelect->where(
                    'bundle_option_table.parent_id IN (?)',
                    $this->getBundleParentIdsQuery($productIds)
                );
            }
        }
    }

    /**
     * @param array $row
     * @param int[] $sectionTypeIds
     * @return array
     */
    private function prepareRowSectionsData(array $row, array $sectionTypeIds)
    {
        $sectionsData = [];

        foreach ($sectionTypeIds as $sectionTypeId) {
            $sectionDataKey = sprintf(self::BASE_SECTION_DATA_KEY, $sectionTypeId);
            $sectionData = $this->feedSectionResource->unserializeSectionData((string) $row[$sectionDataKey]);
            $sectionsData[$sectionTypeId] = $sectionData;
        }

        return $sectionsData;
    }

    /**
     * @param int $storeId
     * @param int[] $sectionTypeIds
     * @param int[] $exportStates
     * @param int $retentionDuration
     * @param bool $includeConfigurableProducts
     * @param bool $includeChildProducts
     * @param int[]|null $productIds
     * @return \Iterator
     */
    public function getExportableProductsIterator(
        $storeId,
        array $sectionTypeIds,
        array $exportStates,
        $retentionDuration,
        $includeConfigurableProducts,
        $includeChildProducts,
        $productIds = null
    ) {
        $productSelect = $this->getExportableProductBaseSelect($storeId, $exportStates, $retentionDuration);

        $this->joinSectionTablesToProductSelect($productSelect, $sectionTypeIds);

        if (!$includeConfigurableProducts) {
            $productSelect->where('product_table.product_id NOT IN (?)', $this->getConfigurableParentIdsQuery());
        }

        if (!$includeChildProducts) {
            $productSelect->where('product_table.product_id NOT IN (?)', $this->getConfigurableChildrenIdsQuery());
        }

        $productSelect->where('product_table.product_id NOT IN (?)', $this->getBundleParentIdsQuery());

        if (is_array($productIds)) {
            $productSelect->where('product_table.product_id IN (?)', $productIds);
        }

        return $this->queryIteratorFactory->create(
            [
                'query' => $productSelect,
                'itemCallback' => function (array $args) use ($sectionTypeIds) {
                    $row = $args['row'];

                    $exportableProduct = $this->exportableProductFactory->create()
                        ->setId((int) $row['product_id'])
                        ->setType(ExportableProduct::TYPE_INDEPENDENT)
                        ->setExportState((int) $row['export_state'])
                        ->setSectionsData($this->prepareRowSectionsData($row, $sectionTypeIds));

                    return $exportableProduct;
                },
            ]
        );
    }

    /**
     * @param int $storeId
     * @param int[] $sectionTypeIds
     * @param int[] $parentExportStates
     * @param int[] $childExportStates
     * @param int $retentionDuration
     * @param int[]|null $productIds
     * @param bool $exportAllChildren
     * @return \Iterator
     */
    public function getExportableConfigurableProductsIterator(
        $storeId,
        array $sectionTypeIds,
        array $parentExportStates,
        array $childExportStates,
        $retentionDuration,
        $productIds = null,
        $exportAllChildren = false
    ) {
        $connection = $this->getConnection();

        $parentSelect = $this->getExportableProductBaseSelect($storeId, $parentExportStates, $retentionDuration);
        $parentSelect->where('product_table.product_id IN (?)', $this->getConfigurableParentIdsQuery($productIds));
        $this->joinSectionTablesToProductSelect($parentSelect, $sectionTypeIds);
        $parentSelect->order('product_id ASC');

        $childrenSelect = $this->getExportableProductBaseSelect($storeId, $childExportStates, $retentionDuration, true);
        $this->joinConfigurableParentIdToChildrenSelect($childrenSelect, $productIds);
        $this->joinSectionTablesToProductSelect($childrenSelect, $sectionTypeIds);
        $childrenSelect->order('parent_id ASC');

        if (is_array($productIds) && !$exportAllChildren) {
            $childrenSelect->where('product_table.product_id IN (?)', $productIds);
        }

        $parentConfigurableAttributeCodes = $this->getParentConfigurableAttributeCodes();
        $childrenQuery = null;
        $previousChildRow = null;

        return $this->queryIteratorFactory->create(
            [
                'query' => $parentSelect,
                'itemCallback' => function ($args) use (
                    $sectionTypeIds,
                    $parentConfigurableAttributeCodes,
                    &$childrenQuery,
                    &$previousChildRow
                ) {
                    $parentRow = $args['row'];
                    $parentId = (int) $parentRow['product_id'];
                    $childRows = [];

                    if (is_array($previousChildRow)) {
                        if ($previousChildRow['parent_id'] === $parentId) {
                            $childRows[] = $previousChildRow;
                            $previousChildRow = null;
                        } elseif ($previousChildRow['parent_id'] < $parentId) {
                            $previousChildRow = null;
                        }
                    }

                    if ((null === $previousChildRow) && ($childrenQuery instanceof \Zend_Db_Statement_Interface)) {
                        while (is_array($childRow = $childrenQuery->fetch())) {
                            $childRow['parent_id'] = (int) $childRow['parent_id'];

                            if ($childRow['parent_id'] > $parentId) {
                                $previousChildRow = $childRow;
                                break;
                            } elseif ($childRow['parent_id'] === $parentId) {
                                $childRows[] = $childRow;
                            }
                        }
                    }

                    $children = [];

                    foreach ($childRows as $childRow) {
                        $children[] = $this->exportableProductFactory->create()
                            ->setId((int) $childRow['product_id'])
                            ->setType(ExportableProduct::TYPE_CHILD)
                            ->setExportState((int) $childRow['export_state'])
                            ->setSectionsData($this->prepareRowSectionsData($childRow, $sectionTypeIds));
                    }

                    $parent = $this->exportableProductFactory->create()
                        ->setId((int) $parentRow['product_id'])
                        ->setType(ExportableProduct::TYPE_PARENT)
                        ->setExportState((int) $parentRow['export_state'])
                        ->setSectionsData($this->prepareRowSectionsData($parentRow, $sectionTypeIds))
                        ->setChildren($children)
                        ->setConfigurableAttributeCodes($parentConfigurableAttributeCodes[$parentId] ?? []);

                    return $parent;
                },
                'rewindCallback' => function () use (
                    $connection,
                    $childrenSelect,
                    &$childrenQuery,
                    &$previousChildRow
                ) {
                    if ($childrenQuery instanceof Zend_Db_Statement_Interface) {
                        $childrenQuery->closeCursor();
                    }

                    $childrenQuery = $connection->query($childrenSelect);
                    $previousChildRow = null;
                },
            ]
        );
    }

    /**
     * @param int $storeId
     * @param int[] $sectionTypeIds
     * @param int[] $parentExportStates
     * @param int[] $childExportStates
     * @param int $retentionDuration
     * @param int[]|null $productIds
     * @param bool $exportAllChildren
     * @return \Iterator
     * @deprecated
     */
    public function getExportableParentProductsIterator(
        $storeId,
        array $sectionTypeIds,
        array $parentExportStates,
        array $childExportStates,
        $retentionDuration,
        $productIds = null,
        $exportAllChildren = false
    ) {
        return $this->getExportableConfigurableProductsIterator(
            $storeId,
            $sectionTypeIds,
            $parentExportStates,
            $childExportStates,
            $retentionDuration,
            $productIds,
            $exportAllChildren
        );
    }

    /**
     * @param $storeId
     * @param array $sectionTypeIds
     * @param array $parentExportStates
     * @param int $retentionDuration
     * @param null $productIds
     */
    public function getExportableBundleProductsIterator(
        $storeId,
        array $sectionTypeIds,
        array $exportStates,
        $retentionDuration,
        $productIds = null
    ) {
        $connection = $this->getConnection();

        $parentSelect = $this->getExportableProductBaseSelect($storeId, $exportStates, $retentionDuration);
        $parentSelect->where('product_table.product_id IN (?)', $this->getBundleParentIdsQuery($productIds));
        $this->joinSectionTablesToProductSelect($parentSelect, $sectionTypeIds);
        $parentSelect->order('product_id ASC');

        // The retrieval of children products is driven by the selections, because we can't afford to miss any.
        $selectionCollection = $this->bundleSelectionCollectionFactory->create();
        $selectionCollection->setFlag('product_children', true);
        $selectionCollection->addFilterByRequiredOptions();

        $childrenSelect = $selectionCollection->getSelect();

        $childrenSelect->joinLeft(
            [ 'product_table' => $this->tableDictionary->getFeedProductTableName() ],
            'product_table.product_id = selection.product_id'
            . ' AND '
            . $connection->quoteInto('product_table.store_id = ?', $storeId),
            [
                'export_state' => $connection->getIfNullSql(
                    'child_export_state',
                    FeedProductInterface::STATE_NOT_EXPORTED
                ),
            ]
        );

        $this->joinBundleParentIdToChildrenSelect($childrenSelect, $productIds);
        $this->joinSectionTablesToProductSelect($childrenSelect, $sectionTypeIds, DbSelect::LEFT_JOIN);

        $childrenSelect->where('is_default', 1);
        $childrenSelect->order('parent_id ASC');

        $childrenQuery = null;
        $previousChildRow = null;

        return $this->queryIteratorFactory->create(
            [
                'query' => $parentSelect,
                'itemCallback' => function ($args) use (
                    $sectionTypeIds,
                    &$childrenQuery,
                    &$previousChildRow
                ) {
                    $parentRow = $args['row'];
                    $parentId = (int) $parentRow['product_id'];
                    $childRows = [];

                    if (is_array($previousChildRow)) {
                        if ($previousChildRow['parent_id'] === $parentId) {
                            $childRows[] = $previousChildRow;
                            $previousChildRow = null;
                        } elseif ($previousChildRow['parent_id'] < $parentId) {
                            $previousChildRow = null;
                        }
                    }

                    if ((null === $previousChildRow) && ($childrenQuery instanceof \Zend_Db_Statement_Interface)) {
                        while (is_array($childRow = $childrenQuery->fetch())) {
                            $childRow['parent_id'] = (int) $childRow['parent_id'];

                            if ($childRow['parent_id'] > $parentId) {
                                $previousChildRow = $childRow;
                                break;
                            } elseif ($childRow['parent_id'] === $parentId) {
                                $childRows[] = $childRow;
                            }
                        }
                    }

                    $children = [];

                    foreach ($childRows as $childRow) {
                        $children[] = $this->exportableProductFactory->create()
                            ->setId((int) $childRow['product_id'])
                            ->setType(ExportableProduct::TYPE_CHILD)
                            ->setExportState((int) $childRow['export_state'])
                            ->setSectionsData($this->prepareRowSectionsData($childRow, $sectionTypeIds))
                            ->setBundledQuantity((int) $childRow['selection_qty']);
                    }

                    $parent = $this->exportableProductFactory->create()
                        ->setId((int) $parentRow['product_id'])
                        ->setType(ExportableProduct::TYPE_BUNDLE)
                        ->setExportState((int) $parentRow['export_state'])
                        ->setSectionsData($this->prepareRowSectionsData($parentRow, $sectionTypeIds))
                        ->setChildren($children);

                    return $parent;
                },
                'rewindCallback' => function () use (
                    $connection,
                    $childrenSelect,
                    &$childrenQuery,
                    &$previousChildRow
                ) {
                    if ($childrenQuery instanceof Zend_Db_Statement_Interface) {
                        $childrenQuery->closeCursor();
                    }

                    $childrenQuery = $connection->query($childrenSelect);
                    $previousChildRow = null;
                },
            ]
        );
    }
}
