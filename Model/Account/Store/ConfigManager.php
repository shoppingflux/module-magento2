<?php

namespace ShoppingFeed\Manager\Model\Account\Store;

use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Account\Store\ConfigInterface as StoreConfigInterface;
use ShoppingFeed\Manager\Model\Feed\ConfigInterface as FeedConfigInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Export\State\ConfigInterface as FeedExportStateConfigInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\ConfigInterface as FeedSectionConfigInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\TypePoolInterface as SectionTypePoolInterface;
use ShoppingFeed\Manager\Model\Sales\Order\ConfigInterface as OrderConfigInterface;

class ConfigManager
{
    /**
     * @var FeedConfigInterface
     */
    private $feedGeneralConfig;

    /**
     * @var FeedExportStateConfigInterface
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
     * @param FeedConfigInterface $feedGeneralConfig
     * @param FeedExportStateConfigInterface $feedExportStateConfig
     * @param SectionTypePoolInterface $sectionTypePool
     * @param OrderConfigInterface $orderGeneralConfig
     */
    public function __construct(
        FeedConfigInterface $feedGeneralConfig,
        FeedExportStateConfigInterface $feedExportStateConfig,
        SectionTypePoolInterface $sectionTypePool,
        OrderConfigInterface $orderGeneralConfig
    ) {
        $this->feedGeneralConfig = $feedGeneralConfig;
        $this->feedExportStateConfig = $feedExportStateConfig;
        $this->sectionTypePool = $sectionTypePool;
        $this->orderGeneralConfig = $orderGeneralConfig;
    }

    /**
     * @return FeedConfigInterface
     */
    public function getFeedGeneralConfig()
    {
        return $this->feedGeneralConfig;
    }

    /**
     * @return FeedExportStateConfigInterface
     */
    public function getFeedExportStateConfig()
    {
        return $this->feedExportStateConfig;
    }

    /**
     * @param $typeCode
     * @return FeedSectionConfigInterface
     * @throws LocalizedException
     */
    public function getSectionTypeConfig($typeCode)
    {
        return $this->sectionTypePool->getTypeByCode($typeCode)->getConfig();
    }

    /**
     * @return OrderConfigInterface
     */
    public function getOrderGeneralConfig()
    {
        return $this->orderGeneralConfig;
    }

    /**
     * @param StoreInterface $store
     * @param StoreConfigInterface $configModel
     * @param array $data
     */
    private function importSubConfigurationData(StoreInterface $store, StoreConfigInterface $configModel, array $data)
    {
        $configObject = $store->getConfiguration();

        if (isset($data[$configModel->getScope()])) {
            $subScopePath = $configModel->getScopeSubPath();
            $subData = $data[$configModel->getScope()];

            foreach ($subScopePath as $pathPart) {
                if (!empty($subData[$pathPart]) && is_array($subData[$pathPart])) {
                    $subData = $subData[$pathPart];
                } else {
                    $subData = false;
                    break;
                }
            }

            if (is_array($subData)) {
                foreach ($subData as $fieldName => $fieldValue) {
                    $field = $configModel->getField($store, $fieldName);

                    if ($field) {
                        $subData[$fieldName] = $field->prepareFormValueForSave($fieldValue);
                    }
                }

                $configObject->setDataByPath(
                    $configModel->getScope() . '/' . implode('/', $subScopePath),
                    $subData
                );
            }
        }
    }

    /**
     * @param StoreInterface $store
     * @param array $data
     */
    public function importStoreData(StoreInterface $store, array $data)
    {
        $this->importSubConfigurationData($store, $this->feedGeneralConfig, $data);
        $this->importSubConfigurationData($store, $this->feedExportStateConfig, $data);

        foreach ($this->sectionTypePool->getTypes() as $sectionType) {
            $this->importSubConfigurationData($store, $sectionType->getConfig(), $data);
        }

        $this->importSubConfigurationData($store, $this->orderGeneralConfig, $data);
    }

    /**
     * @param StoreInterface $store
     * @param string $moduleVersion
     * @return bool
     */
    public function upgradeStoreData(StoreInterface $store, $moduleVersion)
    {
        $originalConfigData = $store->getConfiguration()->getData();

        $this->feedGeneralConfig->upgradeStoreData($store, $this, $moduleVersion);
        $this->feedExportStateConfig->upgradeStoreData($store, $this, $moduleVersion);

        foreach ($this->sectionTypePool->getTypes() as $sectionType) {
            $sectionType->getConfig()->upgradeStoreData($store, $this, $moduleVersion);
        }

        $this->orderGeneralConfig->upgradeStoreData($store, $this, $moduleVersion);

        return $store->getConfiguration()->getData() !== $originalConfigData;
    }
}
