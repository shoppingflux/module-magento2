<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Account\Store\Create;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory as PageResultFactory;
use ShoppingFeed\Manager\Api\AccountRepositoryInterface;
use ShoppingFeed\Manager\Controller\Adminhtml\AccountAction;
use ShoppingFeed\Manager\Controller\Adminhtml\Account\StoreAction;
use ShoppingFeed\Manager\Model\Account\Importer as AccountImporter;


class Existing extends AccountAction
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
     * @param AccountRepositoryInterface $accountRepository
     * @param AccountImporter $accountImporter
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageResultFactory $pageResultFactory,
        AccountRepositoryInterface $accountRepository,
        AccountImporter $accountImporter
    ) {
        $this->accountImporter = $accountImporter;
        parent::__construct($context, $coreRegistry, $pageResultFactory, $accountRepository);
    }

    public function execute()
    {
        $redirectResult = $this->resultRedirectFactory->create();

        if (is_array($storeData = $this->getRequest()->getParam('store'))) {
            try {
                $store = $this->accountImporter->importAccountStoreByShoppingFeedId(
                    $this->getAccount((int) ($storeData['account_id'] ?? 0)),
                    (int) ($storeData['shopping_feed_store_id'] ?? 0),
                    (int) ($storeData['base_store_id'] ?? 0)
                );

                $this->messageManager->addSuccessMessage(__('The account store has been successfully created.'));

                return $redirectResult->setPath(
                    '*/*/edit',
                    [ StoreAction::REQUEST_KEY_STORE_ID => $store->getId() ]
                );
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This account does no longer exist.'));
            } catch (LocalizedException $e) {
                $this->messageManager->addExceptionMessage($e->getPrevious() ?: $e);
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage(
                    $e,
                    __('An error occurred while creating the account store.')
                );
            }
        }

        return $redirectResult->setPath('*/*/create_form_existing');
    }
}
