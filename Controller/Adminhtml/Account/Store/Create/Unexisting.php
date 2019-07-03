<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Account\Store\Create;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory as PageResultFactory;
use ShoppingFeed\Manager\Api\AccountRepositoryInterface;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface as AccountStoreInterface;
use ShoppingFeed\Manager\Controller\Adminhtml\AccountAction;
use ShoppingFeed\Manager\Controller\Adminhtml\Account\StoreAction;
use ShoppingFeed\Manager\Model\Account\Importer as AccountImporter;
use ShoppingFeed\Manager\Ui\DataProvider\Account\Store\Form\Create\Unexisting\DataProvider;

class Unexisting extends AccountAction
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::account_store_create_unexisting';

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

        if (is_array($accountData = $this->getRequest()->getParam(DataProvider::DATA_SCOPE_ACCOUNT))) {
            try {
                $sessionData = $accountData;

                if (isset($sessionData[DataProvider::FIELD_SHOPPING_FEED_PASSWORD])) {
                    unset($sessionData[DataProvider::FIELD_SHOPPING_FEED_PASSWORD]);
                }

                $this->_session->setData(DataProvider::SESSION_DATA_KEY, $sessionData);

                /** @var AccountStoreInterface $store */
                list(, $store) = $this->accountImporter->createAccountAndStore(
                    $accountData[DataProvider::FIELD_BASE_STORE_ID] ?? 0,
                    $accountData[DataProvider::FIELD_EMAIL] ?? '',
                    $accountData[DataProvider::FIELD_SHOPPING_FEED_LOGIN] ?? '',
                    $accountData[DataProvider::FIELD_SHOPPING_FEED_PASSWORD] ?? '',
                    $accountData[DataProvider::FIELD_COUNTRY_ID] ?? ''
                );

                $this->messageManager->addSuccessMessage(__('The account has been successfully created.'));
                $this->_session->getData(DataProvider::SESSION_DATA_KEY, true);

                return $redirectResult->setPath(
                    '*/*/edit',
                    [ StoreAction::REQUEST_KEY_STORE_ID => $store->getId() ]
                );
            } catch (LocalizedException $e) {
                $this->messageManager->addExceptionMessage($e, $e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('An error occurred while creating the account.'));
            }
        }

        return $redirectResult->setPath('*/*/create_form_unexisting');
    }
}

