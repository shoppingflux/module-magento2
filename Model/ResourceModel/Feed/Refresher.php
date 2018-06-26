<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Feed;

use Magento\Framework\Model\ResourceModel\Db\Context as DbContext;
use ShoppingFeed\Manager\Model\Feed\Product as FeedProduct;
use ShoppingFeed\Manager\Model\Feed\ProductFactory as FeedProductFactory;
use ShoppingFeed\Manager\Model\Feed\Product\Filter as ProductFilter;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Filter as SectionFilter;
use ShoppingFeed\Manager\Model\Feed\RefreshableProduct;
use ShoppingFeed\Manager\Model\Feed\RefreshableProductFactory as RefreshableProductFactory;
use ShoppingFeed\Manager\Model\Feed\Refresher as FeedRefresher;
use ShoppingFeed\Manager\Model\ResourceModel\AbstractDb;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product\Filter\Applier as ProductFilterApplier;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product\Section\Filter\Applier as SectionFilterApplier;
use ShoppingFeed\Manager\Model\ResourceModel\Table\Dictionary as TableDictionary;
use ShoppingFeed\Manager\Model\Time\Helper as TimeHelper;


class Refresher extends AbstractDb
{
    /**
     * @var FeedProductFactory
     */
    private $feedProductFactory;

    /**
     * @var RefreshableProductFactory
     */
    private $refreshableProductFactory;

    /**
     * @param DbContext $context
     * @param TimeHelper $timeHelper
     * @param TableDictionary $tableDictionary
     * @param ProductFilterApplier $productFilterApplier
     * @param SectionFilterApplier $sectionFilterApplier
     * @param FeedProductFactory $feedProductFactory
     * @param RefreshableProductFactory $refreshableProductFactory
     * @param string|null $connectionName
     */
    public function __construct(
        DbContext $context,
        TimeHelper $timeHelper,
        TableDictionary $tableDictionary,
        ProductFilterApplier $productFilterApplier,
        SectionFilterApplier $sectionFilterApplier,
        FeedProductFactory $feedProductFactory,
        RefreshableProductFactory $refreshableProductFactory,
        string $connectionName = null
    ) {
        $this->feedProductFactory = $feedProductFactory;
        $this->refreshableProductFactory = $refreshableProductFactory;

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
     * @param int $refreshState
     * @param ProductFilter $productFilter
     * @return $this
     */
    public function forceProductExportStateRefresh($refreshState, ProductFilter $productFilter)
    {
        $this->getConnection()
            ->update(
                $this->tableDictionary->getFeedProductTableName(),
                [
                    'export_state_refresh_state' => $refreshState,
                    'export_state_refresh_state_updated_at' => $this->timeHelper->utcDate(),
                ],
                implode(' AND ', $this->productFilterApplier->getFilterConditions($productFilter))
            );

        return $this;
    }

    /**
     * @param int $refreshState
     * @param SectionFilter $sectionFilter
     * @param ProductFilter $productFilter
     * @return $this
     */
    public function forceProductSectionRefresh(
        $refreshState,
        SectionFilter $sectionFilter,
        ProductFilter $productFilter
    ) {
        $connection = $this->getConnection();
        $storeIds = $sectionFilter->getStoreIds();

        // Update product sections on a store-by-store basis, as update() does not handle aliased target table names,
        // which we would need for filtering products.
        foreach ($storeIds as $storeId) {
            $sectionFilter->setStoreIds([ $storeId ]);
            $productFilter->setStoreIds([ $storeId ]);

            $productSelect = $connection->select()
                ->from([ 'product_table' => $this->tableDictionary->getFeedProductTableName() ], [ 'product_id' ]);

            $this->productFilterApplier->applyFilterToDbSelect($productSelect, $productFilter, 'product_table');

            $connection->update(
                $this->tableDictionary->getFeedProductSectionTableName(),
                [
                    'refresh_state' => $refreshState,
                    'refresh_state_updated_at' => $this->timeHelper->utcDate(),
                ],
                implode(
                    ' AND ',
                    array_merge(
                        [ 'product_id IN (' . $productSelect->assemble() . ')' ],
                        $this->sectionFilterApplier->getFilterConditions($sectionFilter)
                    )
                )
            );
        }

        return $this;
    }

    /**
     * @param bool $isExportStateRefreshed
     * @param int[] $sortedRefreshedSectionTypeIds
     * @return array
     */
    private function getRefreshPriorityWeights($isExportStateRefreshed, $sortedRefreshedSectionTypeIds)
    {
        // Use descending powers of 2 to get weights that can be uniquely associated to a section type or
        // to the export state, and have each weight be strictly greater than the sum of the lesser weights.
        // Given that only two refresh states are taken into account here, it can handle 14 different section types.
        $priorityWeight = pow(2, 31);
        $exportStateWeights = [];
        $sectionTypeWeights = [];
        $refreshStates = [ FeedProduct::REFRESH_STATE_REQUIRED, FeedProduct::REFRESH_STATE_ADVISED ];

        foreach ($refreshStates as $refreshState) {
            if ($isExportStateRefreshed) {
                $exportStateWeights[$refreshState] = $priorityWeight;
                $priorityWeight /= 2;
            }

            foreach ($sortedRefreshedSectionTypeIds as $typeId) {
                $sectionTypeWeights[$typeId][$refreshState] = $priorityWeight;
                $priorityWeight /= 2;
            }
        }

        return [ $exportStateWeights, $sectionTypeWeights ];
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
        $productTableAlias = 'product_table';
        $isExportStateRefreshed = ($exportStateRefreshProductFilter !== null);

        $select = $connection->select()
            ->from([ $productTableAlias => $this->tableDictionary->getFeedProductTableName() ])
            ->where($productTableAlias . '.store_id = ?', $storeId);

        list ($exportStateWeights, $sectionTypeWeights) = $this->getRefreshPriorityWeights(
            $isExportStateRefreshed,
            $sortedRefreshedSectionTypeIds
        );

        $weightExpressions = [];

        if ($isExportStateRefreshed) {
            $exportStateConditions = $this->productFilterApplier->getFilterConditions(
                $exportStateRefreshProductFilter,
                $productTableAlias
            );

            $weightExpressions[] = $connection->getCheckSql(
                implode(' AND ', $exportStateConditions),
                $connection->getCaseSql($productTableAlias . '.export_state_refresh_state', $exportStateWeights, 0),
                0
            );
        }

        foreach ($sortedRefreshedSectionTypeIds as $typeId) {
            $sectionTableAlias = sprintf('section_%d_table', $typeId);
            $productFilter = $refreshedSectionTypeProductFilters[$typeId];
            $sectionFilter = $refreshedSectionTypeSectionFilters[$typeId];
            $sectionFilter->setTypeIds([ $typeId ])->setStoreIds([ $storeId ]);

            $select->joinLeft(
                [ $sectionTableAlias => $this->tableDictionary->getFeedProductSectionTableName() ],
                implode(
                    ' AND ',
                    array_merge(
                        [ $productTableAlias . '.product_id = ' . $sectionTableAlias . '.product_id' ],
                        $this->productFilterApplier->getFilterConditions($productFilter, $productTableAlias),
                        $this->sectionFilterApplier->getFilterConditions($sectionFilter, $sectionTableAlias)
                    )
                ),
                []
            );

            $weightExpressions[] = $connection->getCaseSql(
                $sectionTableAlias . '.refresh_state',
                $sectionTypeWeights[$typeId],
                0
            );
        }

        $select->columns([ 'total_weight' => new \Zend_Db_Expr(implode(' + ', $weightExpressions)) ]);
        $select->order('total_weight DESC');
        $select->having('total_weight > 0');
        $select->limit($maximumCount, 0);

        $refreshableProducts = [];
        $rawProducts = $connection->fetchAll($select);

        foreach ($rawProducts as $rawProductData) {
            $productTotalWeight = (int) $rawProductData['total_weight'];
            unset($rawProductData['total_weight']);

            $feedProduct = $this->feedProductFactory->create();
            $feedProduct->setData($rawProductData);

            $hasRefreshableExportState = false;
            $refreshableSectionTypeIds = [];

            foreach ($exportStateWeights as $weight) {
                if ($productTotalWeight & $weight) {
                    $hasRefreshableExportState = true;
                    $productTotalWeight -= $weight;
                    break;
                }
            }

            if ($productTotalWeight > 0) {
                foreach ($sectionTypeWeights as $typeId => $typeWeights) {
                    foreach ($typeWeights as $weight) {
                        if ($productTotalWeight & $weight) {
                            $refreshableSectionTypeIds[] = $typeId;
                            break;
                        }
                    }
                }
            }

            $refreshableProduct = $this->refreshableProductFactory->create();
            $refreshableProduct->setFeedProduct($feedProduct);
            $refreshableProduct->setHasRefreshableExportState($hasRefreshableExportState);
            $refreshableProduct->setRefreshableSectionTypeIds($refreshableSectionTypeIds);
            $refreshableProducts[] = $refreshableProduct;
        }

        return $refreshableProducts;
    }
}
