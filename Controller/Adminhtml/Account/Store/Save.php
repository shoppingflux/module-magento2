<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Account\Store;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory as PageResultFactory;
use ShoppingFeed\Manager\Api\Account\StoreRepositoryInterface;
use ShoppingFeed\Manager\Controller\Adminhtml\Account\StoreAction;
use ShoppingFeed\Manager\Model\Account\Store\ConfigManager as StoreConfigManager;
use ShoppingFeed\Manager\Model\Feed\Refresher as FeedRefresher;
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
     * @var StoreConfigManager
     */
    private $storeConfigManager;

    /**
     * @var FeedRefresher
     */
    private $feedRefresher;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PageResultFactory $pageResultFactory
     * @param StoreRepositoryInterface $storeRepository
     * @param StoreResourceFactory $storeResourceFactory
     * @param StoreConfigManager $storeConfigManager
     * @param FeedRefresher $feedRefresher
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageResultFactory $pageResultFactory,
        StoreRepositoryInterface $storeRepository,
        StoreResourceFactory $storeResourceFactory,
        StoreConfigManager $storeConfigManager,
        FeedRefresher $feedRefresher
    ) {
        $this->storeResourceFactory = $storeResourceFactory;
        $this->storeConfigManager = $storeConfigManager;
        $this->feedRefresher = $feedRefresher;
        parent::__construct($context, $coreRegistry, $pageResultFactory, $storeRepository);
    }

    public function execute()
    {
        $redirectResult = $this->resultRedirectFactory->create();

        try {
            $store = $this->getStore();
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This account does no longer exist.'));
            return $redirectResult->setPath('*/*/');
        }

        $data = (array) $this->getRequest()->getPostValue();
        $isSaveSuccessful = false;

        try {
            $oldConfiguration = clone $store->getConfiguration();

            $this->storeConfigManager->importStoreData($store, $data);
            $this->storeRepository->save($store);

            $this->feedRefresher->forceOutdatedStoreProductSectionsRefresh(
                $store,
                $oldConfiguration,
                $store->getConfiguration()
            );

            if (isset($data['selected_product_ids']) && is_string($data['selected_product_ids'])) {
                /** @var StoreResource $storeResource */
                $storeResource = $this->storeResourceFactory->create();
                $selectedProductIds = array_filter((array) json_decode($data['selected_product_ids']));
                $storeResource->updateSelectedFeedProducts($store->getId(), $selectedProductIds);
            }

            $isSaveSuccessful = true;
            $this->messageManager->addSuccessMessage(__('The account has been successfully saved.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('An error occurred while saving the account.'));
        }

        if (!$isSaveSuccessful || $this->getRequest()->getParam('back')) {
            return $redirectResult->setPath('*/*/edit', [ self::REQUEST_KEY_STORE_ID => $store->getId() ]);
        }

        return $redirectResult->setPath('*/*/');
    }
}
