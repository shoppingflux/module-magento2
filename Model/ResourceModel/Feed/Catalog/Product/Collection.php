<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Feed\Catalog\Product;

use Magento\Catalog\Model\ResourceModel\Frontend;
use Magento\Catalog\Model\ResourceModel\Product\Collection as BaseCollection;
use Magento\Framework\DB\Adapter\AdapterInterface as DbAdapterInterface;
use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Api\Data\Feed\ProductInterface;
use ShoppingFeed\Manager\Api\Data\Feed\ProductInterface as FeedProductInterface;

class Collection extends BaseCollection
{
    const FLAG_IS_FEED_PRODUCT_TABLE_JOINED = '_sfm_is_feed_product_table_joined_';

    /**
     * @param StoreInterface $store
     * @return $this
     * @throws LocalizedException
     */
    public function joinFeedProductTable(StoreInterface $store)
    {
        if ($this->hasFlag(self::FLAG_IS_FEED_PRODUCT_TABLE_JOINED)) {
            throw new LocalizedException(__('The feed product table has already been joined.'));
        }

        $this->setFlag(self::FLAG_IS_FEED_PRODUCT_TABLE_JOINED, true);

        $feedProductFields = [
            FeedProductInterface::SELECTED_CATEGORY_ID,
            FeedProductInterface::EXPORT_STATE,
            FeedProductInterface::CHILD_EXPORT_STATE,
            FeedProductInterface::EXCLUSION_REASON,
            FeedProductInterface::EXPORT_STATE_REFRESH_STATE,
            FeedProductInterface::EXPORT_STATE_REFRESHED_AT,
            FeedProductInterface::EXPORT_STATE_REFRESH_STATE_UPDATED_AT,
        ];

        $this->joinTable(
            [ 'feed_product_table' => $this->getTable('sfm_feed_product') ],
            'product_id = entity_id',
            array_combine($feedProductFields, $feedProductFields),
            [ FeedProductInterface::STORE_ID => $store->getId() ]
        );

        $this->setStoreId($store->getBaseStoreId());

        return $this;
    }

    /**
     * @param string $alias
     * @param string $attributeCode
     * @return $this
     */
    public function addHasNonEmptyAttributeValueFlagToSelect($alias, $attributeCode)
    {
        $this->addExpressionAttributeToSelect(
            $alias,
            '({{attribute}} IS NOT NULL AND {{attribute}} != "")',
            $attributeCode
        );

        return $this;
    }

    /**
     * @param string $attributeCode
     * @param bool $isNonEmptyValue
     * @return $this
     */
    public function addAttributeValueStateFilter($attributeCode, $isNonEmptyValue)
    {
        $this->addAttributeToFilter(
            [
                [
                    'attribute' => $attributeCode,
                    ($isNonEmptyValue ? 'notnull' : 'null') => true,
                ],
                [
                    'attribute' => $attributeCode,
                    ($isNonEmptyValue ? 'neq' : 'eq') => '',
                ],
            ]
        );

        return $this;
    }

    /**
     * @param string $alias
     * @return $this
     */
    public function addIsVariationFlagToSelect($alias)
    {
        $variationIdsSelect = $this->getConnection()
            ->select()
            ->from($this->getTable('catalog_product_super_link'), [])
            ->columns([ 'product_id' ]);

        $this->addExpressionAttributeToSelect(
            $alias,
            '({{entity_id}} IN (' . $variationIdsSelect->assemble() . '))',
            'entity_id'
        );

        return $this;
    }

    /**
     * @throws LocalizedException
     */
    private function checkCanUseFeedProductTable()
    {
        if (!$this->hasFlag(self::FLAG_IS_FEED_PRODUCT_TABLE_JOINED)) {
            throw new LocalizedException(__('The feed product table has not yet been joined.'));
        }
    }

    /**
     * @param array $exportableForcedCategoryIds
     * @param array $exportableAssignedCategoryIds
     * @return \Zend_Db_Expr
     * @throws LocalizedException
     */
    private function getHasExportableCategoryFlagExpression(
        array $exportableForcedCategoryIds,
        array $exportableAssignedCategoryIds
    ) {
        $this->checkCanUseFeedProductTable();

        $connection = $this->getConnection();

        if (!empty($exportableForcedCategoryIds)) {
            $hasExportableForcedCategoryExpression = $connection->quoteInto(
                $connection->quoteIdentifier(ProductInterface::SELECTED_CATEGORY_ID) . ' IN (?)',
                $exportableForcedCategoryIds
            );
        } else {
            $hasExportableForcedCategoryExpression = new \Zend_Db_Expr('0');
        }

        if (!empty($exportableAssignedCategoryIds)) {
            $categoryFlagsSelect = $connection
                ->select()
                ->from(
                    [ '_ccp_table' => $this->getTable('catalog_category_product') ],
                    [ new \Zend_Db_Expr('1') ]
                )
                ->where('_ccp_table.product_id = e.entity_id')
                ->where('category_id IN (?)', $exportableAssignedCategoryIds);

            $hasExportableAssignedCategoryExpression = 'EXISTS (' . $categoryFlagsSelect->assemble() . ')';
        } else {
            $hasExportableAssignedCategoryExpression = new \Zend_Db_Expr('0');
        }

        return $connection->getIfNullSql(
            '('
            . $hasExportableForcedCategoryExpression
            . ' OR '
            . $hasExportableAssignedCategoryExpression
            . ')',
            '0'
        );
    }

    /**
     * @param string $alias
     * @param array $exportableForcedCategoryIds
     * @param array $exportableAssignedCategoryIds
     * @return $this
     */
    public function addHasExportableCategoryFlagToSelect(
        $alias,
        array $exportableForcedCategoryIds,
        array $exportableAssignedCategoryIds
    ) {
        return $this->addExpressionAttributeToSelect(
            $alias,
            $this->getHasExportableCategoryFlagExpression(
                $exportableForcedCategoryIds,
                $exportableAssignedCategoryIds
            ),
            []
        );
    }

    /**
     * @param array $exportableForcedCategoryIds
     * @param array $exportableAssignedCategoryIds
     * @param bool $isExportableCategory
     * @return $this
     */
    public function addHasExportableCategoryFilter(
        array $exportableForcedCategoryIds,
        array $exportableAssignedCategoryIds,
        $isExportableCategory = true
    ) {
        $this->getSelect()
            ->where(
                $this->getHasExportableCategoryFlagExpression(
                    $exportableForcedCategoryIds,
                    $exportableAssignedCategoryIds
                )
                . ' = '
                . (int) $isExportableCategory
            );

        return $this;
    }

    /**
     * @param string $alias
     * @param int $retentionDuration
     * @return $this
     * @throws LocalizedException
     */
    public function addExportRetentionEndDateToSelect($alias, $retentionDuration)
    {
        $this->checkCanUseFeedProductTable();

        $this->getSelect()
            ->columns(
                [
                    $alias => $this->getConnection()
                        ->getDateAddSql(
                            ProductInterface::EXPORT_RETENTION_STARTED_AT,
                            (int) $retentionDuration,
                            DbAdapterInterface::INTERVAL_SECOND
                        ),
                ]
            );

        return $this;
    }
}
