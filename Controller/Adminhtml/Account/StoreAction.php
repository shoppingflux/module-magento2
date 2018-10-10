<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Account;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page as PageResult;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory as PageResultFactory;
use ShoppingFeed\Manager\Api\Account\StoreRepositoryInterface;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;

abstract class StoreAction extends Action
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::account_stores';
    const REQUEST_KEY_STORE_ID = 'store_id';

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var PageResultFactory
     */
    protected $pageResultFactory;

    /**
     * @var StoreRepositoryInterface
     */
    protected $storeRepository;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PageResultFactory $pageResultFactory
     * @param StoreRepositoryInterface $storeRepository
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageResultFactory $pageResultFactory,
        StoreRepositoryInterface $storeRepository
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->pageResultFactory = $pageResultFactory;
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
            $storeId = (int) $this->getRequest()->getParam(static::REQUEST_KEY_STORE_ID);
        }

        return $this->storeRepository->getById($storeId);
    }

    /**
     * @return PageResult
     */
    protected function initPage()
    {
        /** @var PageResult $pageResult */
        $pageResult = $this->pageResultFactory->create();
        $pageResult->setActiveMenu('ShoppingFeed_Manager::account_stores');
        $pageResult->addBreadcrumb(__('Shopping Feed'), __('Shopping Feed'));
        $pageResult->addBreadcrumb(__('Stores'), __('Stores'));
        $pageResult->getConfig()->getTitle()->prepend(__('Stores'));
        return $pageResult;
    }
}
