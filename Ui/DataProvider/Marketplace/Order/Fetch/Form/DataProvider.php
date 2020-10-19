<?php

namespace ShoppingFeed\Manager\Ui\DataProvider\Marketplace\Order\Fetch\Form;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider as BaseDataProvider;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Account\Store\RegistryConstants as StoreRegistryConstants;
use ShoppingFeed\Manager\Model\Marketplace\Source as MarketplaceSource;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\CollectionFactory as StoreCollectionFactory;

class DataProvider extends BaseDataProvider
{
    const DATA_SCOPE_ORDER = 'marketplace_order';

    const FIELDSET_ORDER = 'marketplace_order';

    const FIELD_STORE_ID = 'store_id';
    const FIELD_CHANNEL_ID = 'channel_id';
    const FIELD_ORDER_REFERENCE = 'order_reference';

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var StoreCollectionFactory
     */
    private $storeCollectionFactory;

    /**
     * @var MarketplaceSource
     */
    private $marketplaceSource;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param Registry $coreRegistry
     * @param StoreCollectionFactory $storeCollectionFactory ,
     * @param MarketplaceSource $marketplaceSource
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
        StoreCollectionFactory $storeCollectionFactory,
        MarketplaceSource $marketplaceSource,
        array $meta = [],
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->storeCollectionFactory = $storeCollectionFactory;
        $this->marketplaceSource = $marketplaceSource;

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
        $storeCollection = $this->storeCollectionFactory->create();

        $storeOptions = $storeCollection->toOptionArray();
        $marketplaceOptions = [];

        /** @var StoreInterface $store */
        foreach ($storeCollection as $store) {
            $this->coreRegistry->unregister(StoreRegistryConstants::CURRENT_ACCOUNT_STORE);
            $this->coreRegistry->register(StoreRegistryConstants::CURRENT_ACCOUNT_STORE, $store);

            foreach ($this->marketplaceSource->toOptionArray() as $marketplaceOption) {
                $channelId = $marketplaceOption['channel_id'];

                if (!isset($marketplaceOptions[$channelId])) {
                    $marketplaceOptions[$channelId] = [
                        'value' => $channelId,
                        'label' => $marketplaceOption['label'],
                        'store_ids' => [],
                    ];
                }

                $marketplaceOptions[$channelId]['store_ids'][] = $store->getId();
            }
        }

        usort(
            $marketplaceOptions,
            function (array $optionA, array $optionB) {
                return strnatcasecmp($optionA['label'], $optionB['label']);
            }
        );

        return array_merge_recursive(
            $this->meta,
            [
                self::FIELDSET_ORDER => [
                    'children' => [
                        self::FIELD_STORE_ID => [
                            'arguments' => [
                                'data' => [
                                    'options' => $storeOptions,
                                ],
                            ],
                        ],
                        self::FIELD_CHANNEL_ID => [
                            'arguments' => [
                                'data' => [
                                    'options' => $marketplaceOptions,
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
