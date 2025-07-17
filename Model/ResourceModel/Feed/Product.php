<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Feed;

use Magento\Framework\Model\ResourceModel\Db\Context as DbContext;
use ShoppingFeed\Manager\Model\Feed\Product\SectionFactory as ProductSectionFactory;
use ShoppingFeed\Manager\Model\ResourceModel\AbstractDb;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Product\SectionFilterApplier;
use ShoppingFeed\Manager\Model\ResourceModel\Table\Dictionary as TableDictionary;
use ShoppingFeed\Manager\Model\TimeHelper;

class Product extends AbstractDb
{
    const EXPORT_STATE_UPDATE_BATCH_SIZE = 1000;

    /**
     * @var int
     */
    private $exportStateUpdateBatchSize;

    /**
     * @var array|null
     */
    private $exportStateBatchedUpdates = null;

    /**
     * @var int
     */
    private $exportStateBatchedUpdateCount = 0;

    /**
     * @param DbContext $context
     * @param TimeHelper $timeHelper
     * @param TableDictionary $tableDictionary
     * @param ProductFilterApplier $productFilterApplier
     * @param SectionFilterApplier $sectionFilterApplier
     * @param string|null $connectionName
     * @param int $exportStateUpdateBatchSize
     */
    public function __construct(
        DbContext $context,
        TimeHelper $timeHelper,
        TableDictionary $tableDictionary,
        ProductFilterApplier $productFilterApplier,
        SectionFilterApplier $sectionFilterApplier,
        ?string $connectionName = null,
        $exportStateUpdateBatchSize = self::EXPORT_STATE_UPDATE_BATCH_SIZE
    ) {
        $this->exportStateUpdateBatchSize = max(1, (int) $exportStateUpdateBatchSize);

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
     * @param int $productId
     * @param int[] $storeIds
     * @return array[]
     */
    public function getProductFeedAttributes($productId, array $storeIds = [])
    {
        $connection = $this->getConnection();

        $attributesSelect = $connection->select()
            ->from(
                $this->tableDictionary->getFeedProductTableName(),
                [ 'store_id', 'is_selected', 'selected_category_id' ]
            )
            ->where('product_id = ?', $productId);

        if (!empty($storeIds)) {
            $attributesSelect->where('store_id IN (?)', $storeIds);
        }

        $storeAttributes = [];

        foreach ($connection->fetchAll($attributesSelect) as $attributes) {
            $storeAttributes[$attributes['store_id']] = [
                'is_selected' => (bool) $attributes['is_selected'],
                'selected_category_id' => empty($attributes['selected_category_id'])
                    ? null
                    : (int) $attributes['selected_category_id'],
            ];
        }

        return $storeAttributes;
    }

    /**
     * @param int|int[] $productIds
     * @param int $storeId
     * @param bool|null $isSelected
     * @param int|false|null $selectedCategoryId
     */
    public function updateProductFeedAttributes($productIds, $storeId, $isSelected, $selectedCategoryId)
    {
        $connection = $this->getConnection();
        $productIds = array_filter(array_map('intval', (array) $productIds));
        $values = [];

        if (null !== $isSelected) {
            $values['is_selected'] = (bool) $isSelected;
        }

        if (null !== $selectedCategoryId) {
            $values['selected_category_id'] = empty($selectedCategoryId) ? null : (int) $selectedCategoryId;
        }

        if (empty($values)) {
            return;
        }

        $connection->update(
            $this->tableDictionary->getFeedProductTableName(),
            $values,
            $connection->quoteInto('store_id = ?', $storeId)
            . ' AND '
            . $connection->quoteInto('product_id IN (?)', $productIds)
        );
    }

    /**
     * @param int $productId
     * @param int $storeId
     * @param int $baseExportState
     * @param int $childExportState
     * @param int|null $exclusionReason
     * @param int $refreshState
     * @param bool $resetRetentionStart
     * @param bool $renewRetentionStart
     * @return $this
     */
    public function updateProductExportStates(
        $productId,
        $storeId,
        $baseExportState,
        $childExportState,
        $exclusionReason,
        $refreshState,
        $resetRetentionStart,
        $renewRetentionStart
    ) {
        $connection = $this->getConnection();
        $now = $this->timeHelper->utcDate();

        $values = [
            'export_state' => $baseExportState,
            'child_export_state' => $childExportState,
            'exclusion_reason' => $exclusionReason,
            'export_state_refreshed_at' => $now,
            'export_state_refresh_state' => $refreshState,
            'export_state_refresh_state_updated_at' => $now,
        ];

        if ($resetRetentionStart) {
            $values['export_retention_started_at'] = null;
        } elseif ($renewRetentionStart) {
            $values['export_retention_started_at'] = $now;
        }

        if (is_array($this->exportStateBatchedUpdates)) {
            $values['product_id'] = $productId;
            $values['store_id'] = $storeId;

            if (array_key_exists('export_retention_started_at', $values)) {
                $this->exportStateBatchedUpdates['with_retention'][] = $values;
            } else {
                $this->exportStateBatchedUpdates['without_retention'][] = $values;
            }

            if (++$this->exportStateBatchedUpdateCount > $this->exportStateUpdateBatchSize) {
                $this->flushExportStateBatchedUpdates();
            }
        } else {
            $connection->update(
                $this->tableDictionary->getFeedProductTableName(),
                $values,
                $connection->quoteInto('product_id = ?', $productId)
                . ' AND '
                . $connection->quoteInto('store_id = ?', $storeId)
            );
        }

        return $this;
    }

    private function flushExportStateBatchedUpdates()
    {
        if (is_array($this->exportStateBatchedUpdates)) {
            $updatedColumns = [
                'export_state',
                'child_export_state',
                'exclusion_reason',
                'export_state_refreshed_at',
                'export_state_refresh_state',
                'export_state_refresh_state_updated_at',
                'export_retention_started_at',
            ];

            if (!empty($this->exportStateBatchedUpdates['with_retention'])) {
                $this->getConnection()
                    ->insertOnDuplicate(
                        $this->tableDictionary->getFeedProductTableName(),
                        $this->exportStateBatchedUpdates['with_retention'],
                        $updatedColumns
                    );
            }

            if (!empty($this->exportStateBatchedUpdates['without_retention'])) {
                $this->getConnection()
                    ->insertOnDuplicate(
                        $this->tableDictionary->getFeedProductTableName(),
                        $this->exportStateBatchedUpdates['without_retention'],
                        $updatedColumns
                    );
            }

            $this->exportStateBatchedUpdates = [
                'with_retention' => [],
                'without_retention' => [],
            ];

            $this->exportStateBatchedUpdateCount = 0;
        }
    }

    public function startExportStateUpdateBatching()
    {
        $this->exportStateBatchedUpdates = [
            'with_retention' => [],
            'without_retention' => [],
        ];

        $this->exportStateBatchedUpdateCount = 0;
    }

    public function stopExportStateUpdateBatching()
    {
        $this->flushExportStateBatchedUpdates();
        $this->exportStateBatchedUpdates = null;
    }
}
