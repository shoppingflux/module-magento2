<?php

namespace ShoppingFeed\Manager\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use ShoppingFeed\Manager\Api\AccountRepositoryInterface;
use ShoppingFeed\Manager\Api\Data\AccountInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Account\CollectionFactory as AccountCollectionFactory;

class EncryptApiTokens implements DataPatchInterface
{
    /**
     * @var AccountCollectionFactory
     */
    private $accountCollectionFactory;

    /**
     * @var AccountRepositoryInterface
     */
    private $accountRepository;

    public function __construct(
        AccountCollectionFactory $accountCollectionFactory,
        AccountRepositoryInterface $accountRepository
    ) {
        $this->accountCollectionFactory = $accountCollectionFactory;
        $this->accountRepository = $accountRepository;
    }

    public function apply()
    {
        $accounts = $this->accountCollectionFactory->create();

        /** @var AccountInterface $account */
        foreach ($accounts as $account) {
            $account->setApiToken($account->getApiToken());
            $this->accountRepository->save($account);
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