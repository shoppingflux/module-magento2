<?php

namespace ShoppingFeed\Manager\Ui\DataProvider\Account\Store\Form;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider as BaseDataProvider;
use Magento\Ui\Component\Form\Fieldset as UiFieldset;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Account\Store\ConfigInterface as StoreConfigInterface;
use ShoppingFeed\Manager\Model\Account\Store\RegistryConstants;
use ShoppingFeed\Manager\Model\Feed\ConfigInterface as FeedConfigInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Export\State\ConfigInterface as ExportStateConfigInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\TypePoolInterface as SectionTypePoolInterface;
use ShoppingFeed\Manager\Model\Sales\Order\ConfigInterface as OrderConfigInterface;


class DataProvider extends BaseDataProvider
{
    const FIELDSET_FEED_GENERAL = 'feed_general';
    const FIELDSET_FEED_EXPORT_STATE = 'feed_export_state';
    const FIELDSET_BASE_FEED_SECTION_TYPE = 'feed_%s';
    const FIELDSET_ORDER_GENERAL = 'order_general';

    const SORT_ORDER_PRODUCT_LIST_FIELDSET = 1000;


    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var FeedConfigInterface
     */
    private $feedGeneralConfig;

    /**
     * @var ExportStateConfigInterface
     */
    private $feedExportStateConfig;

    /**
     * @var SectionTypePoolInterface
     */
    private $sectionTypePool;

    /**
     * @var OrderConfigInterface
     */
    private $orderGeneralConfig;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param Registry $coreRegistry
     * @param FeedConfigInterface $feedGeneralConfig
     * @param ExportStateConfigInterface $feedExportStateConfig
     * @param SectionTypePoolInterface $sectionTypePool
     * @param OrderConfigInterface $orderGeneralConfig
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        Registry $coreRegistry,
        FeedConfigInterface $feedGeneralConfig,
        ExportStateConfigInterface $feedExportStateConfig,
        SectionTypePoolInterface $sectionTypePool,
        OrderConfigInterface $orderGeneralConfig,
        array $meta = [],
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->feedGeneralConfig = $feedGeneralConfig;
        $this->feedExportStateConfig = $feedExportStateConfig;
        $this->sectionTypePool = $sectionTypePool;
        $this->orderGeneralConfig = $orderGeneralConfig;

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
     * @param StoreInterface $store
     * @param StoreConfigInterface $config
     * @param int $sortOrder
     * @return array
     */
    private function getStoreConfigFieldsetMeta(StoreInterface $store, StoreConfigInterface $config, $sortOrder)
    {
        $dataScope = $config->getScope();

        foreach ($config->getScopeSubPath() as $subScope) {
            $dataScope .= '.' . $subScope;
        }

        $childrenMetaConfig = [];

        foreach ($config->getFields($store) as $fieldName => $field) {
            $childrenMetaConfig[$fieldName] = $field->getUiMetaConfig();
        }

        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => $config->getFieldsetLabel(),
                        'componentType' => UiFieldset::NAME,
                        'collapsible' => true,
                        'dataScope' => $dataScope,
                        'sortOrder' => $sortOrder,
                    ],
                ],
            ],
            'children' => $childrenMetaConfig,
        ];
    }

    /**
     * @return array
     */
    public function getMeta()
    {
        /** @var StoreInterface $store */
        $store = $this->coreRegistry->registry(RegistryConstants::CURRENT_ACCOUNT_STORE);

        $this->meta[static::FIELDSET_FEED_GENERAL] = $this->getStoreConfigFieldsetMeta(
            $store,
            $this->feedGeneralConfig,
            10
        );

        $this->meta[static::FIELDSET_FEED_EXPORT_STATE] = $this->getStoreConfigFieldsetMeta(
            $store,
            $this->feedExportStateConfig,
            20
        );

        $sortOrder = 30;

        foreach ($this->sectionTypePool->getSortedTypes() as $sectionType) {
            $fieldsetName = sprintf(static::FIELDSET_BASE_FEED_SECTION_TYPE, $sectionType->getCode());

            $this->meta[$fieldsetName] = $this->getStoreConfigFieldsetMeta(
                $store,
                $sectionType->getConfig(),
                $sortOrder
            );

            $sortOrder += 10;
        }

        $sortOrder = self::SORT_ORDER_PRODUCT_LIST_FIELDSET + 10;

        $this->meta[static::FIELDSET_ORDER_GENERAL] = $this->getStoreConfigFieldsetMeta(
            $store,
            $this->orderGeneralConfig,
            $sortOrder
        );

        return $this->meta;
    }

    /**
     * @param StoreInterface $store
     * @param StoreConfigInterface $config
     * @param array $data
     * @return array
     */
    private function prepareStoreConfigFieldsetData(StoreInterface $store, StoreConfigInterface $config, array $data)
    {
        $dataScope = $config->getScope();

        if (isset($data[$dataScope])) {
            $configData = &$data[$dataScope];
            $hasConfigData = true;

            foreach ($config->getScopeSubPath() as $pathPart) {
                if (isset($configData[$pathPart]) && is_array($configData[$pathPart])) {
                    $configData = &$configData[$pathPart];
                } else {
                    $hasConfigData = false;
                    break;
                }
            }

            if ($hasConfigData) {
                foreach ($config->getFields($store) as $field) {
                    $fieldName = $field->getName();

                    if (array_key_exists($fieldName, $configData)) {
                        $configData[$fieldName] = $field->prepareRawValueForForm($configData[$fieldName]);
                    }
                }
            }
        }

        return $data;
    }

    public function getData()
    {
        /** @var StoreInterface $store */
        $store = $this->coreRegistry->registry(RegistryConstants::CURRENT_ACCOUNT_STORE);
        $storeId = $store->getId();

        $configData = $store->getConfiguration()->getData();
        $configData = $this->prepareStoreConfigFieldsetData($store, $this->feedGeneralConfig, $configData);
        $configData = $this->prepareStoreConfigFieldsetData($store, $this->feedExportStateConfig, $configData);

        foreach ($this->sectionTypePool->getSortedTypes() as $sectionType) {
            $configData = $this->prepareStoreConfigFieldsetData($store, $sectionType->getConfig(), $configData);
        }

        $configData = $this->prepareStoreConfigFieldsetData($store, $this->orderGeneralConfig, $configData);

        $this->data[$storeId] = array_merge(
            $this->data[$storeId] ?? [],
            $configData,
            [ 'store_id' => $storeId ]
        );

        return $this->data;
    }
}
