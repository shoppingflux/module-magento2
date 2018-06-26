<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page as PageResult;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory as PageResultFactory;
use ShoppingFeed\Manager\Api\Data\AccountInterface;
use ShoppingFeed\Manager\Api\AccountRepositoryInterface;


abstract class AccountAction extends Action
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::accounts';
    const REQUEST_KEY_ACCOUNT_ID = 'account_id';

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var PageResultFactory
     */
    protected $pageResultFactory;

    /**
     * @var AccountRepositoryInterface
     */
    protected $accountRepository;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PageResultFactory $pageResultFactory
     * @param AccountRepositoryInterface $accountRepository
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageResultFactory $pageResultFactory,
        AccountRepositoryInterface $accountRepository
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->pageResultFactory = $pageResultFactory;
        $this->accountRepository = $accountRepository;
        parent::__construct($context);
    }

    /**
     * @param int|null $accountId
     * @return AccountInterface
     * @throws NoSuchEntityException
     */
    protected function getAccount($accountId = null)
    {
        if (null === $accountId) {
            $accountId = (int) $this->getRequest()->getParam(static::REQUEST_KEY_ACCOUNT_ID);
        }

        return $this->accountRepository->getById($accountId);
    }

    /**
     * @return PageResult
     */
    protected function initPage()
    {
        /** @var PageResult $pageResult */
        $pageResult = $this->pageResultFactory->create();
        $pageResult->setActiveMenu('ShoppingFeed_Manager::accounts');
        $pageResult->addBreadcrumb(__('Shopping Feed'), __('Shopping Feed'));
        $pageResult->addBreadcrumb(__('Accounts'), __('Accounts'));
        $pageResult->getConfig()->getTitle()->prepend(__('Accounts'));
        return $pageResult;
    }
}
