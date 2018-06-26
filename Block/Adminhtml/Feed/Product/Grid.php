<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Feed\Product;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Backend\Block\Widget\Grid\Extended as ExtendedGrid;
use Magento\Backend\Helper\Data as BackendHelper;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface as AccountStoreInterface;
use ShoppingFeed\Manager\Model\Account\Store\RegistryConstants;


class Grid extends ExtendedGrid
{
    const FLAG_KEY_LIKELY_UNSYNCED_PRODUCT_LIST = 'likely_unsynced_product_list';

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @param Context $context
     * @param BackendHelper $backendHelper
     * @param Registry $coreRegistry
     * @param ProductCollectionFactory $productCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        BackendHelper $backendHelper,
        Registry $coreRegistry,
        ProductCollectionFactory $productCollectionFactory,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->productCollectionFactory = $productCollectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setId('sfm_feed_product_grid');
        $this->setDefaultSort('entity_id');
        $this->setUseAjax(true);
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
        /** @var ProductCollection $collection */
        $collection = $this->getAccountStore()->getCatalogProductCollection();
        $collection->addAttributeToSelect([ 'sku', 'name' ]);
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

        /*
        $this->addColumn(
            'export_state',
            [
                'index' => 'export_state',
                'header' => __('Export State'),
            ]
        );
        */

        // @todo add the (child) export state(s) (and the default category?)

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
