<?php

namespace ShoppingFeed\Manager\Controller\Adminhtml\Account\Token;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory as PageResultFactory;
use ShoppingFeed\Manager\Api\AccountRepositoryInterface;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Api\Data\AccountInterface;
use ShoppingFeed\Manager\Controller\Adminhtml\AccountAction;
use ShoppingFeed\Manager\Model\Account\Importer as AccountImporter;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\CollectionFactory as StoreCollectionFactory;
use ShoppingFeed\Sdk\Api\Session\SessionResource;

class Update extends AccountAction
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::account_token_update';

    /**
     * @var AccountImporter
     */
    private $accountImporter;

    /**
     * @var StoreCollectionFactory
     */
    private $storeCollectionFactory;

    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageResultFactory $pageResultFactory,
        AccountRepositoryInterface $accountRepository,
        AccountImporter $accountImporter,
        StoreCollectionFactory $storeCollectionFactory
    ) {
        $this->accountImporter = $accountImporter;
        $this->storeCollectionFactory = $storeCollectionFactory;
        parent::__construct($context, $coreRegistry, $pageResultFactory, $accountRepository);
    }

    private function isApiSessionMatchingAccount(SessionResource $session, AccountInterface $account)
    {
        if (!$sessionAccountId = (int) $session->getId()) {
            return false;
        }

        if ($account->getShoppingFeedAccountId() === $sessionAccountId) {
            return true;
        }

        $account = clone $account;
        $account->setApiToken($session->getToken());

        $sessionStores = $this->accountImporter->getAccountStoresOptionHash($account);

        $accountStores = $this->storeCollectionFactory
            ->create()
            ->addFieldToFilter(StoreInterface::ACCOUNT_ID, $account->getId())
            ->getItems();

        foreach ($accountStores as $store) {
            if (isset($sessionStores[$store->getShoppingFeedStoreId()])) {
                return true;
            }
        }

        return false;
    }

    public function execute()
    {
        try {
            $account = $this->getAccount();
            $data = $this->getRequest()->getParam('account');

            if (
                is_array($data)
                && ($token = trim((string) ($data['token'] ?? '')))
                && ($session = $this->accountImporter->getApiSessionByToken($token))
                && $this->isApiSessionMatchingAccount($session, $account)
            ) {
                $account->setApiToken($token);
                $this->accountRepository->save($account);

                $this->messageManager->addSuccessMessage(
                    __('The account token has been successfully updated.')
                );
            } else {
                $this->messageManager->addErrorMessage(
                    __('The new token is not associated with the updated account.')
                );
            }
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(
                __('This account does no longer exist.')
            );
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage(
                __($e->getMessage())
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('An error occurred while updating the account token.')
            );
        }

        return $this->resultRedirectFactory->create()->setPath('*/account_store/');
    }
}