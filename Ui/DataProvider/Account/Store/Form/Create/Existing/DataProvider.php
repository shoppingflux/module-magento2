<?php

namespace ShoppingFeed\Manager\Ui\DataProvider\Account\Store\Form\Create\Existing;

use Magento\Backend\Model\Session as BackendSession;
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
    const SESSION_DATA_KEY = 'sfm_account_store_create_existing_data';

    const DATA_SCOPE_STORE = 'store';

    const FIELDSET_STORE = 'store';

    const FIELD_IS_NEW_ACCOUNT = 'is_new_account';
    const FIELD_ACCOUNT_ID = 'account_id';
    const FIELD_SHOPPING_FEED_STORE_ID = 'shopping_feed_store_id';
    const FIELD_USE_API_TOKEN = 'use_api_token';
    const FIELD_API_TOKEN = 'api_token';
    const FIELD_SHOPPING_FEED_LOGIN = 'shopping_feed_login';
    const FIELD_SHOPPING_FEED_PASSWORD = 'shopping_feed_password';
    const FIELD_BASE_STORE_ID = 'base_store_id';

    const NEW_ACCOUNT_ID_VALUE = '__new_account__';

    /**
     * @var BackendSession
     */
    private $backendSession;

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
     * @param BackendSession $backendSession
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
        BackendSession $backendSession,
        Registry $coreRegistry,
        AccountCollectionFactory $accountCollectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->backendSession = $backendSession;
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
        $accountIdSwitcherRules = [];
        $accountIdSwitcherRuleIndex = 1;

        /** @var Account $account */
        foreach ($accountCollection as $account) {
            // Force string values for each ID to ensure that dependencies will work as expected.
            $accountId = $account->getId();

            $accountOptions[] = [
                'value' => (string) $accountId,
                'label' => $account->getShoppingFeedLogin() . ' (' . $account->getApiToken() . ')',
            ];

            $accountIdSwitcherRules[$accountIdSwitcherRuleIndex++] = [
                'actions' => [
                    [
                        'callback' => 'value',
                        'params' => [ 0 ],
                        'target' => '${$.parentName}.' . self::FIELD_IS_NEW_ACCOUNT,
                    ],
                ],
                'value' => (string) $accountId,
            ];

            foreach ($importableStores[$accountId] as $value => $label) {
                $storeOptions[] = [
                    'value' => (string) $value,
                    'label' => $label,
                    'account_id' => (string) $accountId,
                ];
            }
        }

        $hasAnyAccount = !empty($accountOptions);

        $accountOptions[] = [
            'value' => self::NEW_ACCOUNT_ID_VALUE,
            'label' => __('Other Account'),
            'account_id' => null,
        ];

        return array_merge_recursive(
            $this->meta,
            [
                self::FIELDSET_STORE => [
                    'children' => [
                        self::FIELD_IS_NEW_ACCOUNT => [
                            'arguments' => [
                                'data' => [
                                    'value' => $hasAnyAccount ? 0 : 1,
                                ],
                            ],
                        ],
                        self::FIELD_ACCOUNT_ID => [
                            'arguments' => [
                                'data' => [
                                    'options' => $accountOptions,
                                    'config' => [
                                        'switcherConfig' => [
                                            'rules' => $accountIdSwitcherRules,
                                        ],
                                        'visible' => $hasAnyAccount ? 1 : 0,
                                    ],
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
        if (is_array($sessionData = $this->backendSession->getData(self::SESSION_DATA_KEY, true))) {
            $this->data = array_merge_recursive(
                $this->data,
                [ null => [ self::DATA_SCOPE_STORE => $sessionData ] ]
            );
        }

        return $this->data;
    }
}
