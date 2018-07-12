<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Marketplace;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page as PageResult;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory as PageResultFactory;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface;
use ShoppingFeed\Manager\Api\Marketplace\OrderRepositoryInterface;


abstract class OrderAction extends Action
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::marketplace_orders';
    const REQUEST_KEY_ORDER_ID = 'order_id';

    /**
     * @var PageResultFactory
     */
    protected $pageResultFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @param Context $context
     * @param PageResultFactory $pageResultFactory
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Context $context,
        PageResultFactory $pageResultFactory,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->pageResultFactory = $pageResultFactory;
        $this->orderRepository = $orderRepository;
        parent::__construct($context);
    }

    /**
     * @param int|null $orderId
     * @return OrderInterface
     * @throws NoSuchEntityException
     */
    protected function getOrder($orderId = null)
    {
        if (null === $orderId) {
            $orderId = (int) $this->getRequest()->getParam(static::REQUEST_KEY_ORDER_ID);
        }

        return $this->orderRepository->getById($orderId);
    }

    /**
     * @return PageResult
     */
    protected function initPage()
    {
        /** @var PageResult $pageResult */
        $pageResult = $this->pageResultFactory->create();
        $pageResult->setActiveMenu('ShoppingFeed_Manager::marketplace_orders');
        $pageResult->addBreadcrumb(__('Shopping Feed'), __('Shopping Feed'));
        $pageResult->addBreadcrumb(__('Marketplace Orders'), __('Marketplace Orders'));
        $pageResult->getConfig()->getTitle()->prepend(__('Marketplace Orders'));
        return $pageResult;
    }
}
