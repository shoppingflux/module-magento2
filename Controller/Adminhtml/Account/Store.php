<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Account;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use ShoppingFeed\Manager\Api\Account\StoreRepositoryInterface;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;


abstract class Store extends Action
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::stores';

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var StoreRepositoryInterface
     */
    protected $storeRepository;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PageFactory $resultPageFactory
     * @param StoreRepositoryInterface $storeRepository
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        StoreRepositoryInterface $storeRepository
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->resultPageFactory = $resultPageFactory;
        $this->storeRepository = $storeRepository;
        parent::__construct($context);
    }

    /**
     * @param int|null $storeId
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    protected function getStore($storeId = null)
    {
        if (null === $storeId) {
            $storeId = (int) $this->getRequest()->getParam('store_id');
        }

        return $this->storeRepository->getById($storeId);
    }

    /**
     * @return Page
     */
    protected function initPage()
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('ShoppingFeed_Manager::stores');
        $resultPage->addBreadcrumb(__('Shopping Feed'), __('Shopping Feed'));
        $resultPage->addBreadcrumb(__('Stores'), __('Stores'));
        $resultPage->getConfig()->getTitle()->prepend(__('Stores'));
        return $resultPage;
    }
}
