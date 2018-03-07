<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;


abstract class Account extends Action
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::accounts';

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * @return Page
     */
    protected function initPage()
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('ShoppingFeed_Manager::accounts');
        $resultPage->addBreadcrumb(__('Shopping Feed'), __('Shopping Feed'));
        $resultPage->addBreadcrumb(__('Accounts'), __('Accounts'));
        $resultPage->getConfig()->getTitle()->prepend(__('Accounts'));
        return $resultPage;
    }
}
