<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Feed\Product;

use ShoppingFeed\Manager\Model\ResourceModel\AbstractDb;

class Section extends AbstractDb
{
    const SECTION_DATA_UPDATE_BATCH_SIZE = 500;

    /**
     * @var array|null
     */
    private $sectionDataBatchedUpdates = null;

    /**
     * @var int
     */
    private $sectionDataBatchedUpdateCount = 0;

    protected function _construct()
    {
        $this->_init('sfm_feed_product_section', 'section_id');
    }

    /**
     * @param array $data
     * @return string
     */
    public function serializeSectionData(array $data)
    {
        return json_encode($data, JSON_FORCE_OBJECT);
    }

    /**
     * @param string $data
     * @return array
     */
    public function unserializeSectionData($data)
    {
        return ('' !== trim($data)) ? (array) json_decode($data, true) : [];
    }

    /**
     * @param int $productId
     * @param int $storeId
     * @param int $sectionTypeId
     * @param array $data
     * @param int $newRefreshState
     * @return $this
     */
    public function updateSectionData($sectionTypeId, $productId, $storeId, array $data, $newRefreshState)
    {
        $connection = $this->getConnection();
        $now = $this->timeHelper->utcDate();

        $values = [
            'data' => $this->serializeSectionData($data),
            'refreshed_at' => $now,
            'refresh_state' => $newRefreshState,
            'refresh_state_updated_at' => $now,
        ];

        if (is_array($this->sectionDataBatchedUpdates)) {
            $values['type_id'] = $sectionTypeId;
            $values['product_id'] = $productId;
            $values['store_id'] = $storeId;
            $this->sectionDataBatchedUpdates[] = $values;

            if (++$this->sectionDataBatchedUpdateCount > static::SECTION_DATA_UPDATE_BATCH_SIZE) {
                $this->flushSectionDataBatchedUpdates();
            }
        } else {
            $connection->update(
                $this->tableDictionary->getFeedProductSectionTableName(),
                $values,
                $connection->quoteInto('type_id = ?', $sectionTypeId)
                . ' AND '
                . $connection->quoteInto('product_id = ?', $productId)
                . ' AND '
                . $connection->quoteInto('store_id = ?', $storeId)
            );
        }

        return $this;
    }

    private function flushSectionDataBatchedUpdates()
    {
        if (is_array($this->sectionDataBatchedUpdates) && !empty($this->sectionDataBatchedUpdates)) {
            $this->getConnection()
                ->insertOnDuplicate(
                    $this->tableDictionary->getFeedProductSectionTableName(),
                    $this->sectionDataBatchedUpdates
                );

            $this->sectionDataBatchedUpdates = [];
            $this->sectionDataBatchedUpdateCount = 0;
        }
    }

    public function startSectionDataUpdateBatching()
    {
        $this->sectionDataBatchedUpdates = [];
        $this->sectionDataBatchedUpdateCount = 0;
    }

    public function stopSectionDataUpdateBatching()
    {
        $this->flushSectionDataBatchedUpdates();
        $this->sectionDataBatchedUpdates = null;
    }
}
