<?php

namespace ShoppingFeed\Manager\Ui\DataProvider\Account\Store\Form\Create\Existing;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider as BaseDataProvider;
use ShoppingFeed\Manager\Model\Account;
use ShoppingFeed\Manager\Model\Account\Store\RegistryConstants;
use ShoppingFeed\Manager\Model\ResourceModel\Account\CollectionFactory as AccountCollectionFactory;


class DataProvider extends BaseDataProvider
{
    const FIELDSET_STORE = 'store';

    const FIELD_ACCOUNT_ID = 'account_id';
    const FIELD_SHOPPING_FEED_STORE_ID = 'shopping_feed_store_id';

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var AccountCollectionFactory
     */
    private $accountCollectionFactory;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param Registry $coreRegistry
     * @param AccountCollectionFactory $accountCollectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        Registry $coreRegistry,
        AccountCollectionFactory $accountCollectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->accountCollectionFactory = $accountCollectionFactory;

        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
    }

    public function getMeta()
    {
        $importableStores = $this->coreRegistry->registry(RegistryConstants::IMPORTABLE_SHOPPING_FEED_STORES);

        $accountIds = array_keys($importableStores);
        $accountOptions = [];
        $storeOptions = [];
        $accountCollection = $this->accountCollectionFactory->create();
        $accountCollection->addIdFilter($accountIds);

        /** @var Account $account */
        foreach ($accountCollection as $account) {
            // Force string values for each ID to ensure that dependencies will work as expected.
            $accountId = $account->getId();

            $accountOptions[] = [
                'value' => (string) $accountId,
                'label' => $account->getShoppingFeedLogin(),
            ];

            foreach ($importableStores[$accountId] as $value => $label) {
                $storeOptions[] = [
                    'value' => (string) $value,
                    'label' => $label,
                    'account_id' => (string) $accountId,
                ];
            }
        }

        return array_merge_recursive(
            $this->meta,
            [
                self::FIELDSET_STORE => [
                    'children' => [
                        self::FIELD_ACCOUNT_ID => [
                            'arguments' => [
                                'data' => [
                                    'options' => $accountOptions,
                                ],
                            ],
                        ],
                        self::FIELD_SHOPPING_FEED_STORE_ID => [
                            'arguments' => [
                                'data' => [
                                    'options' => $storeOptions,
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );
    }

    public function getData()
    {
        return $this->data;
    }
}
