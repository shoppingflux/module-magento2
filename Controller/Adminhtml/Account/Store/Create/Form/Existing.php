<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Account\Store\Create\Form;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory as PageResultFactory;
use ShoppingFeed\Manager\Api\Account\StoreRepositoryInterface;
use ShoppingFeed\Manager\Controller\Adminhtml\Account\StoreAction;
use ShoppingFeed\Manager\Model\Account\Importer as AccountImporter;
use ShoppingFeed\Manager\Model\Account\Store\RegistryConstants;

class Existing extends StoreAction
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::account_store_create_existing';

    /**
     * @var AccountImporter
     */
    private $accountImporter;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PageResultFactory $pageResultFactory
     * @param StoreRepositoryInterface $storeRepository
     * @param AccountImporter $accountImporter
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageResultFactory $pageResultFactory,
        StoreRepositoryInterface $storeRepository,
        AccountImporter $accountImporter
    ) {
        $this->accountImporter = $accountImporter;
        parent::__construct($context, $coreRegistry, $pageResultFactory, $storeRepository);
    }

    public function execute()
    {
        try {
            $importableStores = $this->accountImporter->getAllImportableStoresOptionHashes();
        } catch (\Exception $e) {
            $importableStores = false;

            $this->messageManager->addExceptionMessage(
                $e,
                __('$An error occurred while fetching the importable Shopping Feed stores.')
            );
        }

        if (empty($importableStores)) {
            if (is_array($importableStores)) {
                $this->messageManager->addNoticeMessage(
                    __('There is no importable Shopping Feed store corresponding to the currently imported accounts.')
                );
            }

            $redirectResult = $this->resultRedirectFactory->create();
            return $redirectResult->setPath('*/*/');
        }

        $this->coreRegistry->register(RegistryConstants::IMPORTABLE_SHOPPING_FEED_STORES, $importableStores);
        $pageResult = $this->initPage();
        $pageResult->addBreadcrumb(__('New Store - Existing'), __('New Store - Existing'));
        $pageResult->getConfig()->getTitle()->prepend(__('New Store - Existing'));
        return $pageResult;
    }
}
