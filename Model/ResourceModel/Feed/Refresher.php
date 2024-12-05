<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Feed;

use Magento\Framework\DB\Adapter\AdapterInterface as DbAdapterInterface;
use Magento\Framework\Model\ResourceModel\Db\Context as DbContext;
use ShoppingFeed\Manager\Api\Data\Feed\Product\SectionInterface as FeedSectionInterface;
use ShoppingFeed\Manager\Api\Data\Feed\ProductInterface as FeedProductInterface;
use ShoppingFeed\Manager\Model\Feed\Product as FeedProduct;
use ShoppingFeed\Manager\Model\Feed\Product\SectionFilter;
use ShoppingFeed\Manager\Model\Feed\ProductFactory as FeedProductFactory;
use ShoppingFeed\Manager\Model\Feed\ProductFilter;
use ShoppingFeed\Manager\Model\Feed\RefreshableProduct;
use ShoppingFeed\Manager\Model\Feed\RefreshableProductFactory as RefreshableProductFactory;
use ShoppingFeed\Manager\Model\Feed\Refresher as FeedRefresher;
use ShoppingFeed\Manager\Model\ResourceModel\AbstractDb;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\CollectionFactory as StoreCollectionFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product\SectionFilterApplier;
use ShoppingFeed\Manager\Model\ResourceModel\Table\Dictionary as TableDictionary;
use ShoppingFeed\Manager\Model\TimeHelper;

class Refresher extends AbstractDb
{
    const DEFAULT_ADVISED_REFRESH_PRIORITIZATION_DELAY = 24 * 30 * 12; // ~ 1 year

    /**
     * @var StoreCollectionFactory
     */
    private $storeCollectionFactory;

    /**
     * @var int[]|null
     */
    private $allStoreIds = null;

    /**
     * @var FeedProductFactory
     */
    private $feedProductFactory;

    /**
     * @var RefreshableProductFactory
     */
    private $refreshableProductFactory;

    /**
     * @var int
     */
    private $advisedRefreshPrioritizationDelay;

    /**
     * @param DbContext $context
     * @param TimeHelper $timeHelper
     * @param TableDictionary $tableDictionary
     * @param StoreCollectionFactory $storeCollectionFactory
     * @param ProductFilterApplier $productFilterApplier
     * @param SectionFilterApplier $sectionFilterApplier
     * @param FeedProductFactory $feedProductFactory
     * @param RefreshableProductFactory $refreshableProductFactory
     * @param string|null $connectionName
     * @param int $advisedRefreshPrioritizationDelay
     */
    public function __construct(
        DbContext $context,
        TimeHelper $timeHelper,
        TableDictionary $tableDictionary,
        StoreCollectionFactory $storeCollectionFactory,
        ProductFilterApplier $productFilterApplier,
        SectionFilterApplier $sectionFilterApplier,
        FeedProductFactory $feedProductFactory,
        RefreshableProductFactory $refreshableProductFactory,
        string $connectionName = null,
        $advisedRefreshPrioritizationDelay = self::DEFAULT_ADVISED_REFRESH_PRIORITIZATION_DELAY
    ) {
        $this->storeCollectionFactory = $storeCollectionFactory;
        $this->feedProductFactory = $feedProductFactory;
        $this->refreshableProductFactory = $refreshableProductFactory;
        $this->advisedRefreshPrioritizationDelay = max(0, (int) $advisedRefreshPrioritizationDelay);

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
     * @param int $newRefreshState
     * @param int|null $minimumRefreshState
     * @return int[]
     */
    public function getOverridableRefreshStates($newRefreshState, $minimumRefreshState = null)
    {
        $overridableStates = [];

        switch ($newRefreshState) {
            /** @noinspection PhpMissingBreakStatementInspection */
            case FeedProduct::REFRESH_STATE_REQUIRED:
                $overridableStates[] = FeedProduct::REFRESH_STATE_ADVISED;
            // Intentional fall-through
            case FeedProduct::REFRESH_STATE_ADVISED:
                $overridableStates[] = FeedProduct::REFRESH_STATE_UP_TO_DATE;
        }

        if (null !== $minimumRefreshState) {
            $overridableStates = array_diff(
                $overridableStates,
                $this->getOverridableRefreshStates($minimumRefreshState)
            );
        }

        return $overridableStates;
    }

    /**
     * @return int[]
     */
    private function getAllStoreIds()
    {
        if (null === $this->allStoreIds) {
            $storeCollection = $this->storeCollectionFactory->create();
            $this->allStoreIds = $storeCollection->getAllIds();
        }

        return $this->allStoreIds;
    }

    /**
     * @param string $productIdFieldName
     * @param string $checkedDateFieldName
     * @param int|null $minimumDelay
     * @return \Zend_Db_Expr
     */
    private function getUpdatedInCatalogProductCondition($productIdFieldName, $checkedDateFieldName, $minimumDelay)
    {
        $connection = $this->getConnection();

        if (null !== $minimumDelay) {
            $catalogUpdateDateField = $connection->getDateSubSql(
                'updated_at',
                (int) $minimumDelay,
                DbAdapterInterface::INTERVAL_SECOND
            );
        } else {
            $catalogUpdateDateField = 'updated_at';
        }

        $updatedCatalogIdSelect = $connection->select()
            ->from($this->tableDictionary->getCatalogProductTableName(), [ 'entity_id' ])
            ->where('entity_id = ' . $productIdFieldName)
            ->where($catalogUpdateDateField . ' > ' . $checkedDateFieldName);

        return new \Zend_Db_Expr('EXISTS (' . $updatedCatalogIdSelect->assemble() . ')');
    }

    /**
     * @param int $refreshState
     * @param ProductFilter $productFilter
     * @param bool $updatedInCatalogOnly
     * @param int|null $catalogUpdateMinimumDelay
     */
    public function forceProductExportStateRefresh(
        $refreshState,
        ProductFilter $productFilter,
        $updatedInCatalogOnly = false,
        $catalogUpdateMinimumDelay = null
    ) {
        $connection = $this->getConnection();
        $conditions = $this->productFilterApplier->getFilterConditions($productFilter);

        $conditions[] = $connection->quoteInto(
            FeedProductInterface::EXPORT_STATE_REFRESH_STATE . ' IN (?)',
            $this->getOverridableRefreshStates($refreshState)
        );

        if ($updatedInCatalogOnly) {
            $conditions[] = $this->getUpdatedInCatalogProductCondition(
                FeedProductInterface::PRODUCT_ID,
                FeedProductInterface::EXPORT_STATE_REFRESHED_AT,
                $catalogUpdateMinimumDelay
            );
        }

        $connection->update(
            $this->tableDictionary->getFeedProductTableName(),
            [
                FeedProductInterface::EXPORT_STATE_REFRESH_STATE => $refreshState,
                FeedProductInterface::EXPORT_STATE_REFRESH_STATE_UPDATED_AT => $this->timeHelper->utcDate(),
            ],
            implode(' AND ', $conditions)
        );
    }

    /**
     * @param int $refreshState
     * @param SectionFilter $sectionFilter
     * @param ProductFilter $productFilter
     * @param bool $updatedInCatalogOnly
     * @param int|null $catalogUpdateMinimumDelay
     */
    public function forceProductSectionRefresh(
        $refreshState,
        SectionFilter $sectionFilter,
        ProductFilter $productFilter,
        $updatedInCatalogOnly = false,
        $catalogUpdateMinimumDelay = null
    ) {
        $connection = $this->getConnection();
        $storeIds = $sectionFilter->getStoreIds();

        if (null === $storeIds) {
            $storeIds = $this->getAllStoreIds();
        }

        $baseConditions = [
            $connection->quoteInto(
                FeedSectionInterface::REFRESH_STATE . ' IN (?)',
                $this->getOverridableRefreshStates($refreshState)
            ),
        ];

        if ($updatedInCatalogOnly) {
            $baseConditions[] = $this->getUpdatedInCatalogProductCondition(
                FeedSectionInterface::PRODUCT_ID,
                FeedSectionInterface::REFRESHED_AT,
                $catalogUpdateMinimumDelay
            );
        }

        // Update product sections on a store-by-store basis, as update() does not handle aliased target table names,
        // which would be needed for filtering products.
        foreach ($storeIds as $storeId) {
            $sectionFilter->setStoreIds([ $storeId ]);

            $storeConditions = array_merge(
                $baseConditions,
                $this->sectionFilterApplier->getFilterConditions($sectionFilter)
            );

            if (!$productFilter->isEmpty()) {
                $productFilter->setStoreIds([ $storeId ]);

                $productSelect = $connection->select()
                    ->from([ 'product_table' => $this->tableDictionary->getFeedProductTableName() ], [ 'product_id' ]);

                $this->productFilterApplier->applyFilterToDbSelect($productSelect, $productFilter, 'product_table');

                $storeConditions[] = 'product_id IN (' . $productSelect->assemble() . ')';
            }

            $connection->update(
                $this->tableDictionary->getFeedProductSectionTableName(),
                [
                    FeedSectionInterface::REFRESH_STATE => $refreshState,
                    FeedSectionInterface::REFRESH_STATE_UPDATED_AT => $this->timeHelper->utcDate(),
                ],
                implode(' AND ', $storeConditions)
            );
        }
    }

    /**
     * @param string $refreshStateExpression
     * @param string $refreshedAtExpression
     * @return \Zend_Db_Expr
     */
    private function getLastRefreshAtExpression($refreshStateExpression, $refreshedAtExpression)
    {
        $connection = $this->getConnection();

        $minRefreshDate = new \Zend_Db_Expr(
            $connection->quote(
                $this->timeHelper->utcPastDate(86400 * 365 * 30)
            )
        );

        return $connection->getIfNullSql(
            $connection->getCheckSql(
                $connection->quoteInto(
                    $refreshStateExpression . ' = ?',
                    FeedProduct::REFRESH_STATE_REQUIRED
                ),
                $refreshedAtExpression,
                $connection->getDateAddSql(
                    $refreshedAtExpression,
                    $this->advisedRefreshPrioritizationDelay,
                    DbAdapterInterface::INTERVAL_HOUR
                )
            ),
            $minRefreshDate
        );
    }

    /**
     * @param int $storeId
     * @param ProductFilter|null $exportStateRefreshProductFilter
     * @param string[] $sortedRefreshedSectionTypeIds
     * @param ProductFilter[] $refreshedSectionTypeProductFilters
     * @param SectionFilter[] $refreshedSectionTypeSectionFilters
     * @param int $maximumCount
     * @return RefreshableProduct[]
     */
    public function getRefreshableProducts(
        $storeId,
        ProductFilter $exportStateRefreshProductFilter = null,
        array $sortedRefreshedSectionTypeIds = [],
        array $refreshedSectionTypeProductFilters = [],
        array $refreshedSectionTypeSectionFilters = [],
        $maximumCount = FeedRefresher::REFRESHABLE_SLICE_SIZE
    ) {
        if ((null === $exportStateRefreshProductFilter) && empty($sortedRefreshedSectionTypeIds)) {
            return [];
        }

        $connection = $this->getConnection();
        $refreshableProductExportStates = [];
        $refreshableProductSectionTypes = [];
        $refreshableProductOldestRefresh = [];

        if ($exportStateRefreshProductFilter !== null) {
            $lastRefreshAtExpression = $this->getLastRefreshAtExpression(
                'export_state_refresh_state',
                'export_state_refreshed_at'
            );

            $select = $connection->select()
                ->from(
                    $this->tableDictionary->getFeedProductTableName(),
                    [
                        'product_id',
                        'last_refresh_at' => $lastRefreshAtExpression,
                    ]
                )
                ->where('store_id = ?', $storeId)
                ->where(
                    implode(
                        ' AND ',
                        $this->productFilterApplier->getFilterConditions($exportStateRefreshProductFilter)
                    )
                )
                ->order('last_refresh_at', 'asc')
                ->limit($maximumCount, 0);

            foreach ($connection->fetchAll($select) as $row) {
                $refreshableProductExportStates[$row['product_id']] = true;
                $refreshableProductOldestRefresh[$row['product_id']] = $row['last_refresh_at'];
            }
        }

        if (!empty($sortedRefreshedSectionTypeIds)) {
            $productTableAlias = 'product_table';
            $sectionTableAlias = 'section_table';

            $lastRefreshAtExpression = $this->getLastRefreshAtExpression(
                $sectionTableAlias . '.refresh_state',
                $sectionTableAlias . '.refreshed_at'
            );

            $select = $connection->select()
                ->from(
                    [ $productTableAlias => $this->tableDictionary->getFeedProductTableName() ],
                    [ 'product_id' ]
                )
                ->joinInner(
                    [ $sectionTableAlias => $this->tableDictionary->getFeedProductSectionTableName() ],
                    $productTableAlias . '.product_id = ' . $sectionTableAlias . '.product_id'
                    . ' AND '
                    . $connection->quoteInto($sectionTableAlias . '.store_id = ?', $storeId),
                    [
                        'type_ids' => new \Zend_Db_Expr(
                            'GROUP_CONCAT(' . $sectionTableAlias . '.type_id)'
                        ),
                        'last_refresh_at' => new \Zend_Db_Expr('MIN(' . $lastRefreshAtExpression . ')'),
                    ]
                )
                ->where($productTableAlias . '.store_id = ?', $storeId);

            foreach ($sortedRefreshedSectionTypeIds as $typeId) {
                $productFilter = $refreshedSectionTypeProductFilters[$typeId];
                $sectionFilter = $refreshedSectionTypeSectionFilters[$typeId];
                $sectionFilter->unsetStoreIds();
                $sectionFilter->setTypeIds([ $typeId ]);

                $conditionSets[] = '('
                    . implode(
                        ' AND ',
                        array_merge(
                            $this->productFilterApplier->getFilterConditions($productFilter, $productTableAlias),
                            $this->sectionFilterApplier->getFilterConditions($sectionFilter, $sectionTableAlias)
                        )
                    )
                    . ')';
            }

            $select->where(implode(' OR ', $conditionSets));
            $select->group($productTableAlias . '.product_id');
            $select->order('last_refresh_at', 'asc');
            $select->limit($maximumCount, 0);

            foreach ($connection->fetchAll($select) as $row) {
                if (!isset($refreshableProductOldestRefresh[$row['product_id']])) {
                    $refreshableProductOldestRefresh[$row['product_id']] = $row['last_refresh_at'];
                } else {
                    $refreshableProductOldestRefresh[$row['product_id']] = min(
                        $row['last_refresh_at'],
                        $refreshableProductOldestRefresh[$row['product_id']]
                    );
                }

                $refreshableProductSectionTypes[$row['product_id']] = array_map(
                    'intval',
                    explode(',', $row['type_ids'])
                );
            }
        }

        ksort($refreshableProductOldestRefresh);
        $refreshableProductIds = array_slice(array_keys($refreshableProductOldestRefresh), 0, $maximumCount);

        if (empty($refreshableProductIds)) {
            return [];
        }

        $feedProductData = [];
        $refreshableProducts = [];

        $select = $connection->select()
            ->from($this->tableDictionary->getFeedProductTableName())
            ->where('store_id = ?', $storeId)
            ->where('product_id IN (?)', $refreshableProductIds);

        foreach ($connection->fetchAll($select) as $row) {
            $feedProductData[$row['product_id']] = $row;
        }

        foreach ($refreshableProductIds as $productId) {
            if (isset($feedProductData[$productId])) {
                $feedProduct = $this->feedProductFactory->create();
                $feedProduct->setData($feedProductData[$productId]);

                $refreshableProduct = $this->refreshableProductFactory->create();
                $refreshableProduct->setFeedProduct($feedProduct);
                $refreshableProduct->setHasRefreshableExportState(isset($refreshableProductExportStates[$productId]));
                $refreshableProduct->setRefreshableSectionTypeIds($refreshableProductSectionTypes[$productId] ?? []);

                $refreshableProducts[] = $refreshableProduct;
            }
        }

        return $refreshableProducts;
    }
}
