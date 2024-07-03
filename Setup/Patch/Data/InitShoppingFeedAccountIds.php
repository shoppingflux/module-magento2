<?php

namespace ShoppingFeed\Manager\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use ShoppingFeed\Manager\Api\AccountRepositoryInterface;
use ShoppingFeed\Manager\Api\Data\AccountInterface;
use ShoppingFeed\Manager\Model\Account\Importer as AccountImporter;
use ShoppingFeed\Manager\Model\ResourceModel\Account\CollectionFactory as AccountCollectionFactory;

class InitShoppingFeedAccountIds implements DataPatchInterface
{
    /**
     * @var AccountCollectionFactory
     */
    private $accountCollectionFactory;

    /**
     * @var AccountRepositoryInterface
     */
    private $accountRepository;

    /**
     * @var AccountImporter
     */
    private $accountImporter;

    public function __construct(
        AccountCollectionFactory $accountCollectionFactory,
        AccountRepositoryInterface $accountRepository,
        AccountImporter $accountImporter
    ) {
        $this->accountCollectionFactory = $accountCollectionFactory;
        $this->accountRepository = $accountRepository;
        $this->accountImporter = $accountImporter;
    }

    public function apply()
    {
        $accounts = $this->accountCollectionFactory->create();

        /** @var AccountInterface $account */
        foreach ($accounts as $account) {
            if ($accountId = $this->accountImporter->getShoppingFeedAccountIdByToken($account->getApiToken())) {
                $account->setShoppingFeedAccountId($accountId);
                $this->accountRepository->save($account);
            }
        }
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}