<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Marketplace;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface;


class Order extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('sfm_marketplace_order', OrderInterface::ORDER_ID);
    }

    /**
     * @param AbstractModel $object
     * @return $this|void
     * @throws LocalizedException
     */
    protected function _afterSave(AbstractModel $object)
    {
        /** @var OrderInterface $object */
        parent::_afterSave($object);
        $connection = $this->getConnection();

        $actualSalesOrderId = (int) $connection->fetchOne(
            $connection->select()
                ->from($this->getMainTable(), [ OrderInterface::SALES_ORDER_ID ])
                ->where('order_id = ?', $object->getId())
        );

        if ($object->getSalesOrderId() !== $actualSalesOrderId) {
            throw new LocalizedException(__('A marketplace order can only be imported once.'));
        }
    }

    protected function prepareDataForUpdate($object)
    {
        $data = parent::prepareDataForUpdate($object);

        if (isset($data[OrderInterface::SALES_ORDER_ID])) {
            // Prevent importing marketplace orders twice by only updating the `sales_order_id` field when it is empty
            // or when it has the same value as the one we are saving.
            // If no update does actually take place, the check in _afterSave() will throw an exception.
            $connection = $this->getConnection();
            $salesOrderId = $data[OrderInterface::SALES_ORDER_ID];

            $data[OrderInterface::SALES_ORDER_ID] = $connection->getCheckSql(
                $connection->prepareSqlCondition(
                    OrderInterface::SALES_ORDER_ID,
                    [ [ 'null' => true ], [ 'eq' => $salesOrderId ] ]
                ),
                $connection->quote($salesOrderId),
                $connection->quoteIdentifier(OrderInterface::SALES_ORDER_ID)
            );
        }

        return $data;
    }


    /**
     * @param int $orderId
     * @throws LocalizedException
     */
    public function bumpOrderImportTryCount($orderId)
    {
        $connection = $this->getConnection();

        $connection->update(
            $this->getMainTable(),
            [ 'import_remaining_try_count' => new \Zend_Db_Expr('import_remaining_try_count - 1') ],
            $connection->quoteInto('order_id = ?', $orderId)
        );
    }
}
