<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Feed\Product;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Backend\Block\Widget\Grid\Column\Filter\Select as SelectFilter;
use Magento\Backend\Block\Widget\Grid\Extended as ExtendedGrid;
use Magento\Backend\Helper\Data as BackendHelper;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatusSource;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Directory\Model\Currency;
use Magento\Framework\DB\Adapter\AdapterInterface as DbAdapterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;
use ShoppingFeed\Manager\Api\Data\Feed\ProductInterface as FeedProductInterface;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface as AccountStoreInterface;
use ShoppingFeed\Manager\Block\Adminhtml\Feed\Product\Grid\Column\Renderer\State as StateRenderer;
use ShoppingFeed\Manager\Controller\Adminhtml\Account\Store\FeedProductSections as ProductSectionsAction;
use ShoppingFeed\Manager\Model\Account\Store\RegistryConstants;
use ShoppingFeed\Manager\Model\Feed\Product\Export\State\ConfigInterface as ExportStateConfigInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Export\State\Source as ProductExportStateSource;
use ShoppingFeed\Manager\Model\Feed\Product\Exclusion\Reason\Source as ProductExclusionReasonSource;
use ShoppingFeed\Manager\Model\Feed\Product\Refresh\State\Source as ProductRefreshStateSource;
use ShoppingFeed\Manager\Model\ResourceModel\Table\Dictionary as TableDictionary;

class Grid extends ExtendedGrid
{
    const FLAG_KEY_LIKELY_UNSYNCED_PRODUCT_LIST = 'likely_unsynced_product_list';

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var TableDictionary
     */
    private $tableDictionary;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var ExportStateConfigInterface
     */
    private $exportStateConfig;

    /**
     * @var ProductType
     */
    private $productType;

    /**
     * @var ProductVisibility
     */
    private $productVisibility;

    /**
     * @var ProductStatusSource
     */
    private $productStatusSource;

    /**
     * @var ProductExportStateSource
     */
    private $productExportStateSource;

    /**
     * @var ProductExclusionReasonSource
     */
    private $productExclusionReasonSource;

    /**
     * @var ProductRefreshStateSource
     */
    private $productRefreshStateSource;

    /**
     * @param Context $context
     * @param BackendHelper $backendHelper
     * @param Registry $coreRegistry
     * @param TableDictionary $tableDictionary
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ExportStateConfigInterface $exportStateConfig
     * @param ProductType $productType
     * @param ProductVisibility $productVisibility
     * @param ProductStatusSource $productStatusSource
     * @param ProductExportStateSource $productExportStateSource
     * @param ProductExclusionReasonSource $productExclusionReasonSource
     * @param ProductRefreshStateSource $productRefreshStateSource
     * @param array $data
     */
    public function __construct(
        Context $context,
        BackendHelper $backendHelper,
        Registry $coreRegistry,
        TableDictionary $tableDictionary,
        ProductCollectionFactory $productCollectionFactory,
        ExportStateConfigInterface $exportStateConfig,
        ProductType $productType,
        ProductVisibility $productVisibility,
        ProductStatusSource $productStatusSource,
        ProductExportStateSource $productExportStateSource,
        ProductExclusionReasonSource $productExclusionReasonSource,
        ProductRefreshStateSource $productRefreshStateSource,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->tableDictionary = $tableDictionary;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->exportStateConfig = $exportStateConfig;
        $this->productType = $productType;
        $this->productVisibility = $productVisibility;
        $this->productStatusSource = $productStatusSource;
        $this->productExportStateSource = $productExportStateSource;
        $this->productExclusionReasonSource = $productExclusionReasonSource;
        $this->productRefreshStateSource = $productRefreshStateSource;
        parent::__construct($context, $backendHelper, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setId('sfm_feed_product_grid');
        $this->setDefaultSort('entity_id');
        $this->setUseAjax(true);
        $this->setSaveParametersInSession(true);
    }

    /**
     * @return AccountStoreInterface
     */
    public function getAccountStore()
    {
        return $this->coreRegistry->registry(RegistryConstants::CURRENT_ACCOUNT_STORE);
    }

    protected function _prepareCollection()
    {
        $store = $this->getAccountStore();

        /** @var ProductCollection $collection */
        $collection = $store->getCatalogProductCollection();
        $connection = $collection->getConnection();

        $collection->addAttributeToSelect([ 'sku', 'name', 'price', 'status', 'visibility' ]);

        if (
            $this->exportStateConfig->shouldRetainPreviouslyExported($store)
            && ($retentionDuration = $this->exportStateConfig->getPreviouslyExportedRetentionDuration($store))
            && ($retentionDuration > 0)
        ) {
            $collection->getSelect()
                ->columns(
                    [
                        'export_retention_ending_at' => $connection->getDateAddSql(
                            'export_retention_started_at',
                            $retentionDuration,
                            DbAdapterInterface::INTERVAL_SECOND
                        ),
                    ]
                );
        }

        $variationIdsSelect = $connection->select()
            ->from($this->tableDictionary->getConfigurableProductLinkTableName(), [])
            ->columns([ 'product_id']);

        $collection->addExpressionAttributeToSelect(
            'is_variation',
            '({{entity_id}} IN (' . $variationIdsSelect->assemble() . '))',
            'entity_id'
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * @param Column $column
     * @return $this
     * @throws LocalizedException
     */
    protected function _addColumnFilterToCollection($column)
    {
        if ($column->getId() == 'is_selected') {
            $productIds = $this->getSelectedProductIds();

            if (empty($productIds)) {
                $productIds = 0;
            }

            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('entity_id', [ 'in' => $productIds ]);
            } elseif ($productIds) {
                $this->getCollection()->addFieldToFilter('entity_id', [ 'nin' => $productIds ]);
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'is_selected',
            [
                'type' => 'checkbox',
                'name' => 'is_selected',
                'values' => $this->getSelectedProductIds(),
                'index' => 'entity_id',
                'header_css_class' => 'col-select col-massaction',
                'column_css_class' => 'col-select col-massaction',
            ]
        );

        $this->addColumn(
            'entity_id',
            [
                'index' => 'entity_id',
                'header' => __('ID'),
                'type' => 'number',
                'sortable' => true,
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
            ]
        );

        $this->addColumn(
            'sku',
            [
                'index' => 'sku',
                'header' => __('SKU'),
            ]
        );

        $this->addColumn(
            'name',
            [
                'index' => 'name',
                'header' => __('Name'),
            ]
        );

        $this->addColumn(
            'type_id',
            [
                'index' => 'type_id',
                'header' => __('Type'),
                'type' => 'options',
                'options' => $this->productType->getOptionArray(),
            ]
        );

        $this->addColumn(
            'status',
            [
                'index' => 'status',
                'header' => __('Status'),
                'type' => 'options',
                'options' => $this->productStatusSource->getOptionArray(),
            ]
        );

        $this->addColumn(
            'visibility',
            [
                'index' => 'visibility',
                'header' => __('Visibility'),
                'type' => 'options',
                'options' => $this->productVisibility->getOptionArray(),
            ]
        );

        $this->addColumn(
            'price',
            [
                'index' => 'price',
                'header' => __('Price'),
                'type' => 'currency',
                'currency_code' => (string) $this->_scopeConfig->getValue(
                    Currency::XML_PATH_CURRENCY_BASE,
                    ScopeInterface::SCOPE_STORE
                ),
            ]
        );

        $this->addColumn(
            'is_variation',
            [
                'index' => 'is_variation',
                'header' => __('Is Variation'),
                'type' => 'options',
                'filter' => false,
                'options' => [ __('No'), __('Yes') ],
            ]
        );

        $this->addColumn(
            FeedProductInterface::EXPORT_STATE,
            [
                'index' => FeedProductInterface::EXPORT_STATE,
                'header' => __('Feed State - Main'),
                'filter' => SelectFilter::class,
                'renderer' => StateRenderer::class,
                'options' => $this->productExportStateSource->toOptionArray(),
                'until_date_index' => 'export_retention_ending_at',
            ]
        );

        $this->addColumn(
            FeedProductInterface::EXCLUSION_REASON,
            [
                'index' => FeedProductInterface::EXCLUSION_REASON,
                'header' => __('Feed State - Exclusion Reason'),
                'type' => 'options',
                'options' => $this->productExclusionReasonSource->toOptionHash(),
            ]
        );

        $this->addColumn(
            FeedProductInterface::CHILD_EXPORT_STATE,
            [
                'index' => FeedProductInterface::CHILD_EXPORT_STATE,
                'header' => __('Feed State - Variation'),
                'filter' => SelectFilter::class,
                'renderer' => StateRenderer::class,
                'options' => $this->productExportStateSource->toOptionArray(),
            ]
        );

        $this->addColumn(
            FeedProductInterface::EXPORT_STATE_REFRESH_STATE,
            [
                'index' => FeedProductInterface::EXPORT_STATE_REFRESH_STATE,
                'header' => __('Feed State - Status'),
                'filter' => SelectFilter::class,
                'renderer' => StateRenderer::class,
                'options' => $this->productRefreshStateSource->toOptionArray(),
                'refresh_date_index' => FeedProductInterface::EXPORT_STATE_REFRESHED_AT,
            ]
        );

        $sectionsDetailsModalLinkConfig = [
            'ShoppingFeed_Manager/js/modal/ajax/link' => [
                'type' => 'slide',
                'buttons' => [],
                'title' => __('View Sections Details'),
            ],
        ];

        $this->addColumn(
            'sections_details_action',
            [
                'index' => 'entity_id',
                'header' => __('Sections'),
                'type' => 'action',
                'filter' => false,
                'actions' => [
                    [
                        'caption' => __('View Details'),
                        'field' => ProductSectionsAction::REQUEST_KEY_PRODUCT_ID,
                        'data-mage-init' => json_encode($sectionsDetailsModalLinkConfig),
                        'url' => [
                            // The "params" field is broken in (since) 2.3.5:
                            // https://github.com/magento/magento2/commit/6e1822d1b1243a293075e8eef2adc2d6b30d024d
                            'base' => 'shoppingfeed_manager/account_store/feedProductSections'
                                . '/'
                                . ProductSectionsAction::REQUEST_KEY_STORE_ID
                                . '/'
                                . $this->getAccountStore()->getId(),
                        ],
                    ],
                ],
            ]
        );

        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/feedProductGrid', [ '_current' => true ]);
    }

    /**
     * @return string
     */
    public function getSelectedProductIdsParameterName()
    {
        return 'selected_products';
    }

    /**
     * @return int[]
     */
    private function getSelectedProductIds()
    {
        $productIds = $this->getRequest()->getParam($this->getSelectedProductIdsParameterName());

        if (!is_array($productIds)) {
            $productIds = $this->getAccountStore()->getSelectedFeedProductIds();
        }

        return array_filter($productIds);
    }

    /**
     * @return bool
     */
    public function hasLikelyUnsyncedProductList()
    {
        if (!$this->hasData(self::FLAG_KEY_LIKELY_UNSYNCED_PRODUCT_LIST)) {
            /** @var ProductCollection $storeCollection */
            $storeCollection = $this->getAccountStore()->getCatalogProductCollection();
            $storeProductCount = $storeCollection->getSize();

            if ($storeProductCount > 0) {
                /** @var ProductCollection $storeCollection */
                $baseCollection = $this->productCollectionFactory->create();
                $hasLikelyUnsyncedProductList = $storeCollection->getSize() !== $baseCollection->getSize();
            } else {
                $hasLikelyUnsyncedProductList = true;
            }

            $this->setData(self::FLAG_KEY_LIKELY_UNSYNCED_PRODUCT_LIST, $hasLikelyUnsyncedProductList);
        }

        return (bool) $this->getDataByKey(self::FLAG_KEY_LIKELY_UNSYNCED_PRODUCT_LIST);
    }
}
