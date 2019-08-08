<?php

namespace ShoppingFeed\Manager\Model\Account;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\RequestOptions as HttpRequestOptions;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Math\Random as Randomizer;
use Magento\Store\Model\StoreManagerInterface;
use ShoppingFeed\Manager\Api\AccountRepositoryInterface;
use ShoppingFeed\Manager\Api\Account\StoreRepositoryInterface as AccountStoreRepositoryInterface;
use ShoppingFeed\Manager\Api\Data\AccountInterface;
use ShoppingFeed\Manager\Model\Account;
use ShoppingFeed\Manager\Model\AccountFactory;
use ShoppingFeed\Manager\Model\Account\Store as AccountStore;
use ShoppingFeed\Manager\Model\Account\StoreFactory as AccountStoreFactory;
use ShoppingFeed\Manager\Model\Feed\Exporter as FeedExporter;
use ShoppingFeed\Manager\Model\ResourceModel\Account\CollectionFactory as AccountCollectionFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\CollectionFactory as AccountStoreCollectionFactory;
use ShoppingFeed\Manager\Model\ShoppingFeed\Api\SessionManager as ApiSessionManager;
use ShoppingFeed\Sdk\Api\Session\SessionResource as ApiSession;
use ShoppingFeed\Sdk\Api\Store\StoreResource as ApiStore;

class Importer
{
    /**
     * @var Randomizer
     */
    private $randomizer;

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
     * @var FeedExporter
     */
    private $feedExporter;

    /**
     * @param Randomizer $randomizer
     * @param StoreManagerInterface $storeManager
     * @param ApiSessionManager $apiSessionManager
     * @param AccountRepositoryInterface $accountRepository
     * @param AccountFactory $accountFactory
     * @param AccountCollectionFactory $accountCollectionFactory
     * @param AccountStoreRepositoryInterface $accountStoreRepository
     * @param StoreFactory $accountStoreFactory
     * @param AccountStoreCollectionFactory $accountStoreCollectionFactory
     * @param TransactionFactory $transactionFactory
     * @param FeedExporter $feedExporter
     */
    public function __construct(
        Randomizer $randomizer,
        StoreManagerInterface $storeManager,
        ApiSessionManager $apiSessionManager,
        AccountRepositoryInterface $accountRepository,
        AccountFactory $accountFactory,
        AccountCollectionFactory $accountCollectionFactory,
        AccountStoreRepositoryInterface $accountStoreRepository,
        AccountStoreFactory $accountStoreFactory,
        AccountStoreCollectionFactory $accountStoreCollectionFactory,
        TransactionFactory $transactionFactory,
        FeedExporter $feedExporter
    ) {
        $this->randomizer = $randomizer;
        $this->storeManager = $storeManager;
        $this->apiSessionManager = $apiSessionManager;
        $this->accountRepository = $accountRepository;
        $this->accountFactory = $accountFactory;
        $this->accountCollectionFactory = $accountCollectionFactory;
        $this->accountStoreRepository = $accountStoreRepository;
        $this->accountStoreFactory = $accountStoreFactory;
        $this->accountStoreCollectionFactory = $accountStoreCollectionFactory;
        $this->transactionFactory = $transactionFactory;
        $this->feedExporter = $feedExporter;
    }

    /**
     * @return string
     */
    private function generateUniqueFeedFileNameBase()
    {
        try {
            return 'feed_' . $this->randomizer->getRandomString(8);
        } catch (\Exception $e) {
            return 'feed_' . dechex(time());
        }
    }

    /**
     * @param string $login
     * @param string $password
     * @return string
     * @throws LocalizedException
     */
    public function getApiTokenByLogin($login, $password)
    {
        $apiSession = $this->apiSessionManager->getSessionByLogin($login, $password);
        return trim($apiSession->getToken());
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
     * @param string|null $feedFileNameBase
     * @return array Imported Account and AccountStore|null
     * @throws LocalizedException
     * @throws \Exception
     */
    public function importAccountByApiToken(
        $apiToken,
        $shouldImportMainStore = false,
        $baseStoreId = null,
        $feedFileNameBase = null
    ) {
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
                    __('Could not determine the store view to which associate the Shopping Feed account.')
                );
            }

            $accountStore = $this->accountStoreFactory->create();
            $accountStore->setBaseStoreId($baseStore->getId());
            $accountStore->setShoppingFeedStoreId($mainStore->getId());
            $accountStore->setShoppingFeedName($mainStore->getName());

            if (!empty($feedFileNameBase)) {
                $accountStore->setFeedFileNameBase($feedFileNameBase);
            } else {
                $accountStore->setFeedFileNameBase($this->generateUniqueFeedFileNameBase());
            }

            $transaction->addCommitCallback(
                function () use ($account, $accountStore) {
                    $accountStore->setAccountId($account->getId());
                    $this->accountStoreRepository->save($accountStore);
                }
            );
        } else {
            $accountStore = null;
        }

        $transaction->save();

        return [ $account, $accountStore ];
    }

    /**
     * @param AccountInterface $account
     * @param bool $appendMainSuffix
     * @return array
     * @throws LocalizedException
     */
    public function getAccountImportableStoresOptionHash(AccountInterface $account, $appendMainSuffix = false)
    {
        $importableStores = [];
        $apiSession = $this->getApiSessionByToken($account->getApiToken());
        $mainStore = $apiSession->getMainStore();

        /** @var ApiStore $store */
        foreach ($apiSession->getStores() as $store) {
            $isMainStore = $appendMainSuffix && ($store->getId() === $mainStore->getId());
            $importableStores[$store->getId()] = $store->getName() . ($isMainStore ? ' ' . __('(main)') : '');
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
                $this->getAccountImportableStoresOptionHash($account, true),
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
                __('Could not determine the store view to which associate the Shopping Feed account.')
            );
        }

        $accountStore = $this->accountStoreFactory->create();
        $accountStore->setAccountId($account->getId());
        $accountStore->setBaseStoreId($baseStore->getId());
        $accountStore->setShoppingFeedStoreId($shoppingFeedStoreId);
        $accountStore->setShoppingFeedName($importableStores[$shoppingFeedStoreId]);
        $accountStore->setFeedFileNameBase($this->generateUniqueFeedFileNameBase());
        $this->accountStoreRepository->save($accountStore);

        return $accountStore;
    }

    /**
     * @param int $baseStoreId
     * @param string $email
     * @param string $shoppingFeedLogin
     * @param string $shoppingFeedPassword
     * @param string $countryId
     * @return array Imported Account and AccountStore
     * @throws LocalizedException
     */
    public function createAccountAndStore($baseStoreId, $email, $shoppingFeedLogin, $shoppingFeedPassword, $countryId)
    {
        $baseStore = $this->storeManager->getStore($baseStoreId);

        if (empty($baseStoreId) || !$baseStore->getId()) {
            throw new LocalizedException(
                __('Could not determine the store view to which associate the Shopping Feed account.')
            );
        }

        $temporaryStore = $this->accountStoreFactory->create();
        $temporaryStore->setBaseStoreId($baseStoreId);
        $feedFileNameBase = $this->generateUniqueFeedFileNameBase();
        $temporaryStore->setFeedFileNameBase($feedFileNameBase);

        $httpClient = new HttpClient();

        try {
            $response = $httpClient->post(
                'https://connectors.shopping-feed.com/api/magento/register',
                [
                    HttpRequestOptions::JSON => [
                        'name' => $shoppingFeedLogin,
                        'email' => $email,
                        'password' => $shoppingFeedPassword,
                        'feed' => $this->feedExporter->getStoreFeedUrl($temporaryStore),
                        'country' => strtolower($countryId),
                    ],
                ]
            );
        } catch (GuzzleException $e) {
            throw new LocalizedException(__('Could not create the account on Shopping Feed. Please try again later.'));
        }

        $accountData = json_decode($response->getBody(), true);

        if (!is_array($accountData) || !isset($accountData['token']) || empty($accountData['token'])) {
            throw new LocalizedException(__('Could not create the account on Shopping Feed. Please try again later.'));
        }

        try {
            return $this->importAccountByApiToken($accountData['token'], true, $baseStoreId, $feedFileNameBase);
        } catch (\Exception $e) {
            throw new LocalizedException(
                __(
                    'The account was successfully created on Shopping Feed, but could not be imported. You can try importing it again using the corresponding token: "%s".',
                    $accountData['token']
                )
            );
        }
    }
}
