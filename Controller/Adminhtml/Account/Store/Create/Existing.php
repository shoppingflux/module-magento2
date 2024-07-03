<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Account\Store\Create;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory as PageResultFactory;
use ShoppingFeed\Manager\Api\AccountRepositoryInterface;
use ShoppingFeed\Manager\Controller\Adminhtml\Account\StoreAction;
use ShoppingFeed\Manager\Controller\Adminhtml\AccountAction;
use ShoppingFeed\Manager\Model\Account\Importer as AccountImporter;
use ShoppingFeed\Manager\Ui\DataProvider\Account\Store\Form\Create\Existing\DataProvider;

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

        if (is_array($storeData = $this->getRequest()->getParam(DataProvider::DATA_SCOPE_STORE))) {
            try {
                $store = null;
                $sessionData = $storeData;

                if (isset($sessionData[DataProvider::FIELD_SHOPPING_FEED_PASSWORD])) {
                    unset($sessionData[DataProvider::FIELD_SHOPPING_FEED_PASSWORD]);
                }

                $this->_session->setData(DataProvider::SESSION_DATA_KEY, $storeData);

                if ($storeData[DataProvider::FIELD_IS_NEW_ACCOUNT] ?? false) {
                    if (empty($storeData[DataProvider::FIELD_USE_API_TOKEN])) {
                        $apiToken = $this->accountImporter->getApiTokenByLogin(
                            $storeData[DataProvider::FIELD_SHOPPING_FEED_LOGIN] ?? '',
                            $storeData[DataProvider::FIELD_SHOPPING_FEED_PASSWORD] ?? ''
                        );
                    } else {
                        $apiToken = trim($storeData[DataProvider::FIELD_API_TOKEN] ?? '');
                    }

                    list($account) = $this->accountImporter->importAccountByApiToken(
                        $apiToken,
                        false,
                        (int) ($storeData[DataProvider::FIELD_BASE_STORE_ID] ?? 0)
                    );

                    $allStores = $this->accountImporter->getAccountStoresOptionHash($account);
                    $importableStores = $this->accountImporter->getAccountImportableStoresOptionHash($account);

                    if (empty($allStores) || empty($importableStores)) {
                        throw new LocalizedException(
                            __('The Shopping Feed account does not have any importable store.')
                        );
                    } elseif (count($allStores) === 1) {
                        reset($allStores);
                        $shouldImportStore = true;

                        $storeData[DataProvider::FIELD_IS_NEW_ACCOUNT] = false;
                        $storeData[DataProvider::FIELD_ACCOUNT_ID] = $account->getId();
                        $storeData[DataProvider::FIELD_SHOPPING_FEED_STORE_ID] = key($allStores);
                    } else {
                        $shouldImportStore = false;
                    }
                } else {
                    $shouldImportStore = true;
                    $account = $this->getAccount((int) ($storeData[DataProvider::FIELD_ACCOUNT_ID] ?? 0));
                }

                if ($shouldImportStore) {
                    $store = $this->accountImporter->importAccountStoreByShoppingFeedId(
                        $account,
                        (int) ($storeData[DataProvider::FIELD_SHOPPING_FEED_STORE_ID] ?? 0),
                        (int) ($storeData[DataProvider::FIELD_BASE_STORE_ID] ?? 0)
                    );
                }

                if ($account) {
                    if ($store) {
                        $this->messageManager->addSuccessMessage(__('The account has been successfully imported.'));
                        $this->_session->getData(DataProvider::SESSION_DATA_KEY, true);

                        return $redirectResult->setPath(
                            '*/*/edit',
                            [ StoreAction::REQUEST_KEY_STORE_ID => $store->getId() ]
                        );
                    } else {
                        $storeData[DataProvider::FIELD_IS_NEW_ACCOUNT] = false;
                        $storeData[DataProvider::FIELD_ACCOUNT_ID] = $account->getId();
                        unset($storeData[DataProvider::FIELD_API_TOKEN]);
                        unset($storeData[DataProvider::FIELD_SHOPPING_FEED_LOGIN]);
                        $this->_session->setData(DataProvider::SESSION_DATA_KEY, $storeData);
                    }
                }
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This account does no longer exist.'));
            } catch (LocalizedException $e) {
                $this->messageManager->addExceptionMessage($e, $e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('An error occurred while importing the account.'));
            }
        }

        return $redirectResult->setPath('*/*/create_form_existing');
    }
}
