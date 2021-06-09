<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Marketplace\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page as PageResult;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory as PageResultFactory;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\TicketInterface;
use ShoppingFeed\Manager\Api\Marketplace\Order\TicketRepositoryInterface;

abstract class TicketAction extends Action
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::marketplace_order_tickets';
    const REQUEST_KEY_TICKET_ID = 'log_id';

    /**
     * @var PageResultFactory
     */
    protected $pageResultFactory;

    /**
     * @var TicketRepositoryInterface
     */
    protected $ticketRepository;

    /**
     * @param Context $context
     * @param PageResultFactory $pageResultFactory
     * @param TicketRepositoryInterface $ticketRepository
     */
    public function __construct(
        Context $context,
        PageResultFactory $pageResultFactory,
        TicketRepositoryInterface $ticketRepository
    ) {
        $this->pageResultFactory = $pageResultFactory;
        $this->ticketRepository = $ticketRepository;
        parent::__construct($context);
    }

    /**
     * @param int|null $ticketId
     * @return TicketInterface
     * @throws NoSuchEntityException
     */
    protected function getTicket($ticketId = null)
    {
        if (null === $ticketId) {
            $ticketId = (int) $this->getRequest()->getParam(static::REQUEST_KEY_TICKET_ID);
        }

        return $this->ticketRepository->getById($ticketId);
    }

    /**
     * @return PageResult
     */
    protected function initPage()
    {
        /** @var PageResult $pageResult */
        $pageResult = $this->pageResultFactory->create();
        $pageResult->setActiveMenu('ShoppingFeed_Manager::marketplace_order_tickets');
        $pageResult->addBreadcrumb(__('Shopping Feed'), __('Shopping Feed'));
        $pageResult->addBreadcrumb(__('Marketplace Orders'), __('Marketplace Orders'));
        $pageResult->addBreadcrumb(__('Sync Tickets'), __('Sync Tickets'));
        $pageResult->getConfig()->getTitle()->prepend(__('Order Sync Tickets'));
        return $pageResult;
    }
}
