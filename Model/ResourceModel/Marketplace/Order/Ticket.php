<?php

namespace ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\TicketInterface;

class Ticket extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('sfm_marketplace_order_ticket', TicketInterface::TICKET_ID);
    }

    /**
     * @param int $ticketId
     * @return bool
     * @throws LocalizedException
     */
    public function isExistingId($ticketId)
    {
        $connection = $this->getConnection();

        $idSelect = $connection->select()
            ->from($this->getMainTable())
            ->where('ticket_id = ?', $ticketId);

        return !empty($this->getConnection()->fetchOne($idSelect));
    }
}
