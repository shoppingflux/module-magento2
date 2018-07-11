<?php

namespace ShoppingFeed\Manager\Model\Marketplace\Order;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Api\Marketplace\Order\TicketRepositoryInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\TicketInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Ticket as TicketResource;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\TicketFactory as TicketResourceFactory;


class TicketRepository implements TicketRepositoryInterface
{
    /**
     * @var TicketResource
     */
    protected $ticketResource;

    /**
     * @var TicketFactory
     */
    protected $ticketFactory;

    /**
     * @param TicketResourceFactory $ticketResourceFactory
     * @param TicketFactory $ticketFactory
     */
    public function __construct(TicketResourceFactory $ticketResourceFactory, TicketFactory $ticketFactory)
    {
        $this->ticketResource = $ticketResourceFactory->create();
        $this->ticketFactory = $ticketFactory;
    }

    public function save(TicketInterface $ticket)
    {
        try {
            $this->ticketResource->save($ticket);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $ticket;
    }

    public function getById($ticketId)
    {
        $ticket = $this->ticketFactory->create();
        $this->ticketResource->load($ticket, $ticketId);

        if (!$ticket->getId()) {
            throw new NoSuchEntityException(__('Marketplace order ticket with ID "%1" does not exist.', $ticketId));
        }

        return $ticket;
    }
}
