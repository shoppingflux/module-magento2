<?php

namespace ShoppingFeed\Manager\Model\Account;

use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use ShoppingFeed\Manager\Api\AccountRepositoryInterface;
use ShoppingFeed\Manager\Model\Account;
use ShoppingFeed\Manager\Model\ShoppingFeed\Api\Result\Account as ApiAccount;
use ShoppingFeed\Manager\Model\AccountFactory;
use ShoppingFeed\Manager\Model\Account\StoreFactory as AccountStoreFactory;


class Importer
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var AccountRepositoryInterface
     */
    private $accountRepository;

    /**
     * @var AccountFactory
     */
    private $accountFactory;

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
     * @param AccountRepositoryInterface $accountRepository
     * @param AccountFactory $accountFactory
     * @param StoreFactory $accountStoreFactory
     * @param TransactionFactory $transactionFactory
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        AccountRepositoryInterface $accountRepository,
        AccountFactory $accountFactory,
        AccountStoreFactory $accountStoreFactory,
        TransactionFactory $transactionFactory
    ) {
        $this->storeManager = $storeManager;
        $this->accountRepository = $accountRepository;
        $this->accountFactory = $accountFactory;
        $this->accountStoreFactory = $accountStoreFactory;
        $this->transactionFactory = $transactionFactory;
    }

    /**
     * @param ApiAccount $apiAccount
     * @param int $storeId
     * @return Account
     * @throws LocalizedException
     * @throws \Exception
     */
    public function importFromApi(ApiAccount $apiAccount, $storeId)
    {
        $store = $this->storeManager->getStore($storeId);

        if (!$store || !$store->getId()) {
            throw new LocalizedException(__('Invalid store under which to import an API account'));
        }

        $apiToken = $apiAccount->getApiToken();

        try {
            $this->accountRepository->getByApiToken($apiAccount->getApiToken());
            throw new LocalizedException(__('An account with the API token "%1" already exists', $apiToken));
        } catch (NoSuchEntityException $e) {
            // Everything is fine if the API token is not yet used by another account.
        }

        $transaction = $this->transactionFactory->create();
        $account = $this->accountFactory->create();
        $accountStores = [];
        $apiAccountStores = $apiAccount->getStores();
        $accountStoreCount = count($apiAccountStores);

        if (0 === $accountStoreCount) {
            throw new LocalizedException(__('Can not import an API account without store'));
        } elseif ($accountStoreCount > 1) {
            throw new LocalizedException(__('Can not currently import an API account having more than one store'));
        }

        $account->addData(
            [
                'shopping_feed_account_id' => $apiAccount->getId(),
                'api_token' => $apiToken,
                'shopping_feed_login' => $apiAccount->getLogin(),
                'shopping_feed_email' => $apiAccount->getEmail(),
            ]
        );

        $transaction->addObject($account);

        foreach ($apiAccountStores as $apiAccountStore) {
            $accountStore = $this->accountStoreFactory->create();

            $accountStore->addData(
                [
                    'base_store_id' => $store->getId(),
                    'shopping_feed_store_id' => $apiAccountStore->getId(),
                    'shopping_feed_name' => $apiAccountStore->getName(),
                ]
            );

            $accountStores[] = $accountStore;
        }

        $transaction->addCommitCallback(
            function () use ($account, $accountStores) {
                foreach ($accountStores as $accountStore) {
                    $accountStore->setData('account_id', $account->getId());
                    $accountStore->save();
                }
            }
        );

        $transaction->save();

        return $account;
    }
}
