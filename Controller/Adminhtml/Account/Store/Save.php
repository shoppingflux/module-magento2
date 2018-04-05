<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Account\Store;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use ShoppingFeed\Manager\Api\Account\StoreRepositoryInterface;
use ShoppingFeed\Manager\Controller\Adminhtml\Account\StoreAction;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store as StoreResource;
use ShoppingFeed\Manager\Model\ResourceModel\Account\StoreFactory as StoreResourceFactory;


class Save extends StoreAction
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::account_store_edit';

    /**
     * @var StoreResourceFactory
     */
    private $storeResourceFactory;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PageFactory $resultPageFactory
     * @param StoreRepositoryInterface $storeRepository
     * @param StoreResourceFactory $storeResourceFactory
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        StoreRepositoryInterface $storeRepository,
        StoreResourceFactory $storeResourceFactory
    ) {
        $this->storeResourceFactory = $storeResourceFactory;
        parent::__construct($context, $coreRegistry, $resultPageFactory, $storeRepository);
    }

    public function execute()
    {
        $redirectResult = $this->resultRedirectFactory->create();

        try {
            $store = $this->getStore();
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This account store does no longer exist.'));
            return $redirectResult->setPath('*/*/');
        }

        $data = (array) $this->getRequest()->getPostValue();
        $isSaveSuccessful = false;

        try {
            $store->importConfigurationData($data);
            $this->storeRepository->save($store);

            if (isset($data['selected_product_ids']) && is_string($data['selected_product_ids'])) {
                /** @var StoreResource $storeResource */
                $storeResource = $this->storeResourceFactory->create();
                $selectedProductIds = array_filter((array) json_decode($data['selected_product_ids']));
                $storeResource->updateSelectedFeedProducts($store->getId(), $selectedProductIds);
            }

            $isSaveSuccessful = true;
            $this->messageManager->addSuccessMessage(__('The account store has been successfully saved.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addExceptionMessage($e->getPrevious() ?: $e);
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the account store.'));
        }

        if (!$isSaveSuccessful || $this->getRequest()->getParam('back')) {
            return $redirectResult->setPath('*/*/edit', [ 'store_id' => $store->getId() ]);
        }

        return $redirectResult->setPath('*/*/');
    }
}
