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
                [ '_sales_order_table' => $this->tableDictionary->getSalesOrderTableCode() ],
                '_sales_order_table.entity_id = main_table.sales_order_id',
                [ self::KEY_SALES_INCREMENT_ID => 'increment_id' ]
            );

        $this->addFilterToMap(OrderInterface::STORE_ID, 'main_table.' . OrderInterface::STORE_ID);
        $this->addFilterToMap(OrderInterface::SHIPPING_AMOUNT, 'main_table.' . OrderInterface::SHIPPING_AMOUNT);
        $this->addFilterToMap(OrderInterface::CREATED_AT, 'main_table.' . OrderInterface::CREATED_AT);

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
        return $this;
    }

    private function addHandledTicketAbsenceFilter($action)
    {
        $ticketSelect = $this->getConnection()->select()
            ->from(
                [ '_ticket_table' => $this->tableDictionary->getMarketplaceOrderTicketTableCode() ],
                [ TicketInterface::ORDER_ID ]
            )
            ->where('action = ?', $action)
            ->where('status = ?', TicketInterface::STATUS_HANDLED);

        $this->getSelect()
            ->where('main_table.order_id NOT IN (?)', $ticketSelect->assemble());
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
        $shipmentSelect = $this->getConnection()
            ->select()
            ->from(
                [ '_sales_shipment_table' => $this->tableDictionary->getSalesShipmentTableCode() ],
                [ 'order_id' ]
            );

        $this->getSelect()->where('_sales_order_table.entity_id IN (?)', $shipmentSelect->assemble());
        return $this;
    }
}
