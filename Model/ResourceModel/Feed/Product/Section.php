<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Feed\Product;

use ShoppingFeed\Manager\Model\ResourceModel\AbstractDb;

class Section extends AbstractDb
{
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

        $connection->update(
            $this->tableDictionary->getFeedProductSectionTableName(),
            [
                'data' => $this->serializeSectionData($data),
                'refreshed_at' => $now,
                'refresh_state' => $newRefreshState,
                'refresh_state_updated_at' => $now,
            ],
            $connection->quoteInto('type_id = ?', $sectionTypeId)
            . ' AND '
            . $connection->quoteInto('product_id = ?', $productId)
            . ' AND '
            . $connection->quoteInto('store_id = ?', $storeId)
        );

        return $this;
    }
}
