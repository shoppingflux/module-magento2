<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Account\Store;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use ShoppingFeed\Manager\Model\Account\Store;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store as StoreResource;


/**
 * @method StoreResource getResource()
 */
class Collection extends AbstractCollection
{
    protected $_idFieldName = 'store_id';

    protected function _construct()
    {
        $this->_init(Store::class, StoreResource::class);
    }

    /**
     * @param int|int[] $storeIds
     * @return $this
     */
    public function addIdFilter($storeIds)
    {
        if (!is_array($storeIds)) {
            $storeIds = array_map('intval', $storeIds);
        } else {
            $storeIds = [ (int) $storeIds ];
        }

        $this->addFieldToFilter('store_id', [ 'in' => $storeIds ]);
        return $this;
    }
}
