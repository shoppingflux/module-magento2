<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Feed;

use ShoppingFeed\Manager\Model\ResourceModel\AbstractDb;


class Product extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('sfm_feed_product', 'product_id');
    }

    /**
     * @param int $productId
     * @param int $storeId
     * @param int $baseExportState
     * @param int $childExportState
     * @param int $refreshState
     * @param bool $renewRetentionStart
     * @param bool $resetRetentionStart
     * @return $this
     */
    public function updateProductExportStates(
        $productId,
        $storeId,
        $baseExportState,
        $childExportState,
        $refreshState,
        $resetRetentionStart,
        $renewRetentionStart
    ) {
        $connection = $this->getConnection();
        $now = $this->timeHelper->utcDate();

        $values = [
            'export_state' => $baseExportState,
            'child_export_state' => $childExportState,
            'export_state_refreshed_at' => $now,
            'export_state_refresh_state' => $refreshState,
            'export_state_refresh_state_updated_at' => $now,
        ];

        if ($resetRetentionStart) {
            $values['export_retention_started_at'] = null;
        } elseif ($renewRetentionStart) {
            $values['export_retention_started_at'] = $now;
        }

        $connection->update(
            $this->getFeedProductTable(),
            $values,
            $connection->quoteInto('product_id = ?', $productId)
            . ' AND '
            . $connection->quoteInto('store_id = ?', $storeId)
        );

        return $this;
    }
}
