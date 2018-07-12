<?php

namespace ShoppingFeed\Manager\Api\Marketplace\Order;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\TicketInterface;


/**
 * @api
 */
interface TicketRepositoryInterface
{
    /**
     * @param TicketInterface $ticket
     * @return TicketInterface
     * @throws CouldNotSaveException
     */
    public function save(TicketInterface $ticket);

    /**
     * @param int $ticketId
     * @return TicketInterface
     * @throws NoSuchEntityException
     */
    public function getById($ticketId);
}
