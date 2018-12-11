<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Marketplace\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page as PageResult;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory as PageResultFactory;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\LogInterface;
use ShoppingFeed\Manager\Api\Marketplace\Order\LogRepositoryInterface;

abstract class LogAction extends Action
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::marketplace_order_logs';
    const REQUEST_KEY_LOG_ID = 'log_id';

    /**
     * @var PageResultFactory
     */
    protected $pageResultFactory;

    /**
     * @var LogRepositoryInterface
     */
    protected $logRepository;

    /**
     * @param Context $context
     * @param PageResultFactory $pageResultFactory
     * @param LogRepositoryInterface $logRepository
     */
    public function __construct(
        Context $context,
        PageResultFactory $pageResultFactory,
        LogRepositoryInterface $logRepository
    ) {
        $this->pageResultFactory = $pageResultFactory;
        $this->logRepository = $logRepository;
        parent::__construct($context);
    }

    /**
     * @param int|null $logId
     * @return LogInterface
     * @throws NoSuchEntityException
     */
    protected function getLog($logId = null)
    {
        if (null === $logId) {
            $logId = (int) $this->getRequest()->getParam(static::REQUEST_KEY_LOG_ID);
        }

        return $this->logRepository->getById($logId);
    }

    /**
     * @return PageResult
     */
    protected function initPage()
    {
        /** @var PageResult $pageResult */
        $pageResult = $this->pageResultFactory->create();
        $pageResult->setActiveMenu('ShoppingFeed_Manager::marketplace_order_logs');
        $pageResult->addBreadcrumb(__('Shopping Feed'), __('Shopping Feed'));
        $pageResult->addBreadcrumb(__('Marketplace Orders'), __('Marketplace Orders'));
        $pageResult->addBreadcrumb(__('Logs'), __('Logs'));
        $pageResult->getConfig()->getTitle()->prepend(__('Order Logs'));
        return $pageResult;
    }
}
