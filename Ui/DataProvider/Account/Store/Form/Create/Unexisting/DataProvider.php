<?php

namespace ShoppingFeed\Manager\Ui\DataProvider\Account\Store\Form\Create\Unexisting;

use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Backend\Model\Session as BackendSession;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use \Magento\Framework\Filter\FilterManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider as BaseDataProvider;
use Magento\Store\Model\Information as StoreInformation;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface as StoreManager;

class DataProvider extends BaseDataProvider
{
    const SESSION_DATA_KEY = 'sfm_account_store_create_unexisting_data';

    const DATA_SCOPE_ACCOUNT = 'account';

    const FIELDSET_ACCOUNT = 'account';

    const FIELD_BASE_STORE_ID = 'base_store_id';
    const FIELD_EMAIL = 'email';
    const FIELD_SHOPPING_FEED_LOGIN = 'shopping_feed_login';
    const FIELD_SHOPPING_FEED_PASSWORD = 'shopping_feed_password';
    const FIELD_COUNTRY_ID = 'country_id';

    /**
     * @var BackendSession
     */
    private $backendSession;

    /**
     * @var AuthSession
     */
    private $authSession;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var FilterManager
     */
    private $filterManager;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param BackendSession $backendSession
     * @param AuthSession $authSession
     * @param StoreManager $storeManager
     * @param FilterManager $filterManager
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
        AuthSession $authSession,
        StoreManager $storeManager,
        FilterManager $filterManager,
        array $meta = [],
        array $data = []
    ) {
        $this->backendSession = $backendSession;
        $this->authSession = $authSession;
        $this->storeManager = $storeManager;
        $this->filterManager = $filterManager;

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

    /**
     * @param Store $store
     * @return string
     */
    public function getStoreDefaultCountryId(Store $store)
    {
        $countryId = trim($store->getConfig(StoreInformation::XML_PATH_STORE_INFO_COUNTRY_CODE));
        return !empty($defaultCountryId)
            ? $countryId
            : trim($store->getConfig(DirectoryHelper::XML_PATH_DEFAULT_COUNTRY));
    }

    /**
     * @param Store $store
     * @return string
     */
    public function getStoreDefaultLogin(Store $store)
    {
        $storeName = trim($store->getConfig(StoreInformation::XML_PATH_STORE_INFO_NAME));
        $storeName = !empty($storeName) ? $storeName : trim($store->getFrontendName());
        return strtolower(trim(preg_replace('/[^a-zA-Z0-9-]+/', '-', $this->filterManager->translit($storeName)), '-'));
    }

    public function getMeta()
    {
        $baseStoreSwitcherRules = [];

        foreach ($this->storeManager->getStores() as $store) {
            if ($store instanceof Store) {
                $switcherRuleActions = [];

                if (!empty($login = $this->getStoreDefaultLogin($store))) {
                    $switcherRuleActions[] = [
                        'callback' => 'suggest',
                        'params' => [ $login ],
                        'target' => '${$.parentName}.' . self::FIELD_SHOPPING_FEED_LOGIN,
                    ];
                }

                if (!empty($countryId = $this->getStoreDefaultCountryId($store))) {
                    $switcherRuleActions[] = [
                        'callback' => 'suggest',
                        'params' => [ $countryId ],
                        'target' => '${$.parentName}.' . self::FIELD_COUNTRY_ID,
                    ];
                }

                $baseStoreSwitcherRules[] = [
                    'value' => $store->getId(),
                    'actions' => $switcherRuleActions,
                ];
            }
        }

        return array_merge_recursive(
            $this->meta,
            [
                self::FIELDSET_ACCOUNT => [
                    'children' => [
                        self::FIELD_BASE_STORE_ID => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'switcherConfig' => [
                                            'rules' => $baseStoreSwitcherRules,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        self::FIELD_EMAIL => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'default' => $this->authSession->getUser()->getEmail(),
                                    ],
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
                [ null => [ self::DATA_SCOPE_ACCOUNT => $sessionData ] ]
            );
        }

        return $this->data;
    }
}
