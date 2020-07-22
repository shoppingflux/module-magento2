<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order;

use Magento\Sales\Model\Order as SalesOrder;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\TicketInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface;
use ShoppingFeed\Manager\Model\Marketplace\Order;
use ShoppingFeed\Manager\Model\ResourceModel\AbstractCollection;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order as OrderResource;

/**
 * @method OrderResource getResource()
 */
class Collection extends AbstractCollection
{
    const KEY_SALES_INCREMENT_ID = 'sales_increment_id';

    protected $_idFieldName = OrderInterface::ORDER_ID;

    protected function _construct()
    {
        $this->_init(Order::class, OrderResource::class);
    }

    protected function _initSelect()
    {
        parent::_initSelect();

        $this->getSelect()
            ->joinLeft(
                [ '_sales_order_table' => $this->tableDictionary->getSalesOrderTableName() ],
                '_sales_order_table.entity_id = main_table.sales_order_id',
                [ self::KEY_SALES_INCREMENT_ID => 'increment_id' ]
            );

        $this->addFilterToMap(OrderInterface::STORE_ID, 'main_table.' . OrderInterface::STORE_ID);
        $this->addFilterToMap(OrderInterface::SHIPPING_AMOUNT, 'main_table.' . OrderInterface::SHIPPING_AMOUNT);
        $this->addFilterToMap(OrderInterface::CREATED_AT, 'main_table.' . OrderInterface::CREATED_AT);
        $this->addFilterToMap(self::KEY_SALES_INCREMENT_ID, '_sales_order_table.increment_id');

        return $this;
    }

    /**
     * @param int|int[] $storeIds
     * @return $this
     */
    public function addStoreIdFilter($storeIds)
    {
        $this->addFieldToFilter(OrderInterface::STORE_ID, [ 'in' => $this->prepareIdFilterValue($storeIds) ]);
        return $this;
    }

    /**
     * @param bool $isFulfilled
     * @return $this
     */
    public function addIsFulfilledFilter($isFulfilled = true)
    {
        $this->addFieldToFilter(OrderInterface::IS_FULFILLED, (bool) $isFulfilled ? 1 : 0);
        return $this;
    }

    /**
     * @param int|int[] $marketplaceIds
     * @return $this
     */
    public function addMarketplaceIdFilter($marketplaceIds)
    {
        $this->addFieldToFilter(
            OrderInterface::SHOPPING_FEED_MARKETPLACE_ID,
            [ 'in' => $this->prepareIdFilterValue($marketplaceIds) ]
        );

        return $this;
    }

    /**
     * @param string $number
     * @return $this
     */
    public function addMarketplaceNumberFilter($number)
    {
        $this->addFieldToFilter(OrderInterface::MARKETPLACE_ORDER_NUMBER, [ 'like' => $number ]);
        return $this;
    }

    /**
     * @param \DateTime $fromDate
     * @return $this
     */
    public function addCreatedFromDateFilter(\DateTime $fromDate)
    {
        $this->addFieldToFilter(OrderInterface::CREATED_AT, [ 'gteq' => $fromDate->format('Y-m-d') ]);
        return $this;
    }

    /**
     * @return $this
     */
    public function addImportedFilter()
    {
        $this->addFieldToFilter(OrderInterface::SALES_ORDER_ID, [ 'notnull' => true ]);
        return $this;
    }

    /**
     * @return $this
     */
    public function addNonImportedFilter()
    {
        $this->addFieldToFilter(OrderInterface::SALES_ORDER_ID, [ 'null' => true ]);
        return $this;
    }

    /**
     * @return $this
     */
    public function addImportableFilter()
    {
        $this->addFieldToFilter(OrderInterface::IMPORT_REMAINING_TRY_COUNT, [ 'gt' => 0 ]);

        $this->addFieldToFilter(
            [
                OrderInterface::SHOPPING_FEED_STATUS,
                OrderInterface::IS_FULFILLED,
            ],
            [
                // Shopping Feed status ..
                [
                    'in' => [
                        OrderInterface::STATUS_WAITING_SHIPMENT,
                        OrderInterface::STATUS_SHIPPED,
                    ],
                ],
                // .. OR is fulfilled ..
                [ 'eq' => true ]
            ]
        );

        return $this;
    }

    private function addHandledTicketAbsenceFilter($action)
    {
        $ticketSelect = $this->getConnection()->select()
            ->from(
                [ '_ticket_table' => $this->tableDictionary->getMarketplaceOrderTicketTableName() ],
                [ TicketInterface::ORDER_ID ]
            )
            ->where('action = ?', $action)
            ->where('status = ?', TicketInterface::STATUS_HANDLED);

        $this->getSelect()
            ->where('main_table.order_id NOT IN (?)', new \Zend_Db_Expr($ticketSelect->assemble()));
    }

    /**
     * @return $this
     */
    public function addNotifiableImportFilter()
    {
        $this->addImportedFilter();
        $this->addHandledTicketAbsenceFilter(TicketInterface::ACTION_ACKNOWLEDGE_SUCCESS);
        return $this;
    }

    /**
     * @return $this
     */
    public function addNotifiableCancellationFilter()
    {
        $this->getSelect()->where('_sales_order_table.state = ?', SalesOrder::STATE_CANCELED);
        $this->addHandledTicketAbsenceFilter(TicketInterface::ACTION_CANCEL);
        return $this;
    }

    /**
     * @return $this
     */
    public function addNotifiableShipmentFilter()
    {
        $this->addFieldToFilter(OrderInterface::HAS_NON_NOTIFIABLE_SHIPMENT, 0);

        $shipmentSelect = $this->getConnection()
            ->select()
            ->from(
                [ '_sales_shipment_table' => $this->tableDictionary->getSalesShipmentTableName() ],
                [ 'order_id' ]
            );

        $this->getSelect()->where(
            '_sales_order_table.entity_id IN (?)',
            new \Zend_Db_Expr($shipmentSelect->assemble())
        );

        $this->addHandledTicketAbsenceFilter(TicketInterface::ACTION_SHIP);

        return $this;
    }
}
