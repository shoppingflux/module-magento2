<?php

namespace ShoppingFeed\Manager\Model\Account;

use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store as BaseStore;
use Magento\Store\Model\StoreManagerInterface;
use ShoppingFeed\Manager\Api\AccountRepositoryInterface;
use ShoppingFeed\Manager\Api\Account\StoreRepositoryInterface as AccountStoreRepositoryInterface;
use ShoppingFeed\Manager\Api\Data\AccountInterface;
use ShoppingFeed\Manager\Console\Command\Exception;
use ShoppingFeed\Manager\Model\Account;
use ShoppingFeed\Manager\Model\AccountFactory;
use ShoppingFeed\Manager\Model\Account\Store as AccountStore;
use ShoppingFeed\Manager\Model\Account\StoreFactory as AccountStoreFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Account\CollectionFactory as AccountCollectionFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\CollectionFactory as AccountStoreCollectionFactory;
use ShoppingFeed\Manager\Model\ShoppingFeed\Api\SessionManager as ApiSessionManager;
use ShoppingFeed\Sdk\Api\Session\SessionResource as ApiSession;
use ShoppingFeed\Sdk\Api\Store\StoreResource as ApiStore;
use ShoppingFeed\Sdk\Credential\Password as ApiPasswordCredential;
use ShoppingFeed\Sdk\Client\Client as ApiClient;

class Importer
{
    const STORE_CREATION_TOKEN = '18eaf020f7a33c08c63591c52df6a8dd3bd30d99';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ApiSessionManager
     */
    private $apiSessionManager;

    /**
     * @var AccountRepositoryInterface
     */
    private $accountRepository;

    /**
     * @var AccountFactory
     */
    private $accountFactory;

    /**
     * @var AccountCollectionFactory
     */
    private $accountCollectionFactory;

    /**
     * @var AccountStoreCollectionFactory
     */
    private $accountStoreCollectionFactory;

    /**
     * @var AccountStoreRepositoryInterface
     */
    private $accountStoreRepository;

    /**
     * @var AccountStoreFactory
     */
    private $accountStoreFactory;

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ApiSessionManager $apiSessionManager
     * @param AccountRepositoryInterface $accountRepository
     * @param AccountFactory $accountFactory
     * @param AccountCollectionFactory $accountCollectionFactory
     * @param AccountStoreRepositoryInterface $accountStoreRepository
     * @param StoreFactory $accountStoreFactory
     * @param AccountStoreCollectionFactory $accountStoreCollectionFactory
     * @param TransactionFactory $transactionFactory
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ApiSessionManager $apiSessionManager,
        AccountRepositoryInterface $accountRepository,
        AccountFactory $accountFactory,
        AccountCollectionFactory $accountCollectionFactory,
        AccountStoreRepositoryInterface $accountStoreRepository,
        AccountStoreFactory $accountStoreFactory,
        AccountStoreCollectionFactory $accountStoreCollectionFactory,
        TransactionFactory $transactionFactory
    ) {
        $this->storeManager = $storeManager;
        $this->apiSessionManager = $apiSessionManager;
        $this->accountRepository = $accountRepository;
        $this->accountFactory = $accountFactory;
        $this->accountCollectionFactory = $accountCollectionFactory;
        $this->accountStoreRepository = $accountStoreRepository;
        $this->accountStoreFactory = $accountStoreFactory;
        $this->accountStoreCollectionFactory = $accountStoreCollectionFactory;
        $this->transactionFactory = $transactionFactory;
    }

    /**
     * @param string $login
     * @param string $password
     * @return string|null
     */
    public function getApiTokenByLogin($login, $password)
    {
        $passwordCredential = new ApiPasswordCredential($login, $password);

        try {
            $apiSession = ApiClient::createSession($passwordCredential);
            $apiToken = trim($apiSession->getToken());
        } catch (\Exception $e) {
            $apiToken = null;
        }

        return $apiToken;
    }

    /**
     * @param $apiToken
     * @return ApiSession
     * @throws LocalizedException
     */
    private function getApiSessionByToken($apiToken)
    {
        try {
            return $this->apiSessionManager->getSessionByToken($apiToken);
        } catch (\Exception $e) {
            throw new LocalizedException(
                __('Shopping Feed account for API token "%1" does not exist.', $apiToken)
            );
        }
    }

    /**
     * @param string $apiToken
     * @param bool $shouldImportMainStore
     * @param int $baseStoreId
     * @return Account
     * @throws LocalizedException
     * @throws \Exception
     */
    public function importAccountByApiToken($apiToken, $shouldImportMainStore = false, $baseStoreId = null)
    {
        $apiToken = trim($apiToken);

        if (empty($apiToken)) {
            throw new LocalizedException(__('The API token can not be empty.'));
        }

        try {
            $this->accountRepository->getByApiToken($apiToken);
            throw new LocalizedException(__('An account already exists for API token "%1".', $apiToken));
        } catch (NoSuchEntityException $e) {
            // Everything is fine if the API token is not yet used by another account.
        }

        $apiSession = $this->getApiSessionByToken($apiToken);
        $transaction = $this->transactionFactory->create();

        $account = $this->accountFactory->create();
        $account->setApiToken($apiToken);
        $account->setShoppingFeedLogin($apiSession->getLogin());
        $account->setShoppingFeedEmail($apiSession->getEmail());
        $transaction->addObject($account);

        if ($shouldImportMainStore) {
            $mainStore = $apiSession->getMainStore();

            if (null === $mainStore) {
                throw new LocalizedException(
                    __('The Shopping Feed account does not have a main store which to import.')
                );
            }

            if (empty($baseStoreId)) {
                $baseStore = null;
            } else {
                $baseStore = $this->storeManager->getStore($baseStoreId);
            }

            if (empty($baseStoreId) || empty($baseStore) || !$baseStore->getId()) {
                throw new LocalizedException(
                    __('Could not determine the store view to which associate the Shopping Feed account main store.')
                );
            }

            $accountStore = $this->accountStoreFactory->create();
            $accountStore->setBaseStoreId($baseStore->getId());
            $accountStore->setShoppingFeedStoreId($mainStore->getId());
            $accountStore->setShoppingFeedName($mainStore->getName());

            $transaction->addCommitCallback(
                function () use ($account, $accountStore) {
                    $accountStore->setAccountId($account->getId());
                    $this->accountStoreRepository->save($accountStore);
                }
            );
        }

        $transaction->save();

        return $account;
    }

    /**
     * @param AccountInterface $account
     * @return array
     * @throws LocalizedException
     */
    public function getAccountImportableStoresOptionHash(AccountInterface $account)
    {
        $importableStores = [];
        $apiSession = $this->getApiSessionByToken($account->getApiToken());

        /** @var ApiStore $store */
        foreach ($apiSession->getStores() as $store) {
            $importableStores[$store->getId()] = $store->getName();
        }

        return $importableStores;
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function getAllImportableStoresOptionHashes()
    {
        $accountStoreCollection = $this->accountStoreCollectionFactory->create();
        $importedShoppingFeedStores = [];

        /** @var AccountStore $accountStore */
        foreach ($accountStoreCollection as $accountStore) {
            $importedShoppingFeedStores[$accountStore->getShoppingFeedStoreId()] = true;
        }

        $accountCollection = $this->accountCollectionFactory->create();
        $importableStores = [];

        /** @var Account $account */
        foreach ($accountCollection as $account) {
            $accountImportableStores = array_diff_key(
                $this->getAccountImportableStoresOptionHash($account),
                $importedShoppingFeedStores
            );

            if (!empty($accountImportableStores)) {
                $importableStores[$account->getId()] = $accountImportableStores;
            }
        }

        return $importableStores;
    }

    /**
     * @param AccountInterface $account
     * @param int $shoppingFeedStoreId
     * @param int $baseStoreId
     * @return AccountStore
     * @throws LocalizedException
     */
    public function importAccountStoreByShoppingFeedId(AccountInterface $account, $shoppingFeedStoreId, $baseStoreId)
    {
        $importableStores = $this->getAccountImportableStoresOptionHash($account);

        if (!isset($importableStores[$shoppingFeedStoreId])) {
            throw new LocalizedException(
                __('Shopping Feed store for ID "%1" does not exist.', $shoppingFeedStoreId)
            );
        }

        try {
            $this->accountStoreRepository->getByShoppingFeedStoreId($shoppingFeedStoreId);

            throw new LocalizedException(
                __('An account store already exists for Shopping Feed store ID "%1".', $shoppingFeedStoreId)
            );
        } catch (NoSuchEntityException $e) {
            // Everything is fine if the Shopping Feed store ID is not yet used by another store.
        }

        $baseStore = $this->storeManager->getStore($baseStoreId);

        if (empty($baseStoreId) || !$baseStore->getId()) {
            throw new LocalizedException(
                __('Could not determine the store view to which associate the Shopping Feed store.')
            );
        }

        $accountStore = $this->accountStoreFactory->create();
        $accountStore->setAccountId($account->getId());
        $accountStore->setBaseStoreId($baseStore->getId());
        $accountStore->setShoppingFeedStoreId($shoppingFeedStoreId);
        $accountStore->setShoppingFeedName($importableStores[$shoppingFeedStoreId]);
        $this->accountStoreRepository->save($accountStore);

        return $accountStore;
    }
}
