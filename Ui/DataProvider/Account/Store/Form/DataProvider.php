<?php

namespace ShoppingFeed\Manager\Ui\DataProvider\Account\Store\Form;

use Magento\Ui\Component\Form\Fieldset as UiFieldset;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Account\Store\ConfigInterface as StoreConfigInterface;
use ShoppingFeed\Manager\Model\Account\Store\RegistryConstants;
use ShoppingFeed\Manager\Ui\DataProvider\Account\Store\AbstractDataProvider;


class DataProvider extends AbstractDataProvider
{
    const FIELDSET_EXPORT_STATE = 'feed_export_state';
    const FIELDSET_BASE_SECTION_TYPE = 'feed_%s';

    /**
     * @param StoreConfigInterface $config
     * @param int $sortOrder
     * @return array
     */
    private function getStoreConfigFieldsetConfig(StoreConfigInterface $config, $sortOrder)
    {
        $dataScope = $config->getScope();

        foreach ($config->getScopeSubPath() as $subScope) {
            $dataScope .= '.' . $subScope;
        }

        $childrenMetaConfig = [];

        foreach ($config->getFields() as $fieldName => $field) {
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
        $this->meta[static::FIELDSET_EXPORT_STATE] = $this->getStoreConfigFieldsetConfig($this->exportStateConfig, 10);
        $sortOrder = 20;

        foreach ($this->sectionTypePool->getSortedTypes() as $sectionType) {
            $fieldsetName = sprintf(static::FIELDSET_BASE_SECTION_TYPE, $sectionType->getCode());
            $this->meta[$fieldsetName] = $this->getStoreConfigFieldsetConfig($sectionType->getConfig(), $sortOrder);
            $sortOrder += 10;
        }

        return $this->meta;
    }

    /**
     * @param StoreConfigInterface $config
     * @param array $data
     * @return array
     */
    private function prepareStoreConfigFieldsetData(StoreConfigInterface $config, array $data)
    {
        $dataScope = $config->getScope();

        if (isset($data[$dataScope])) {
            $configData =& $data[$dataScope];
            $hasConfigData = true;

            foreach ($config->getScopeSubPath() as $pathPart) {
                if (isset($configData[$pathPart]) && is_array($configData[$pathPart])) {
                    $configData =& $configData[$pathPart];
                } else {
                    $hasConfigData = false;
                    break;
                }
            }

            if ($hasConfigData) {
                foreach ($config->getFields() as $field) {
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
        $configData = $store->getConfiguration()->getData();
        $configData = $this->prepareStoreConfigFieldsetData($this->exportStateConfig, $configData);

        foreach ($this->sectionTypePool->getSortedTypes() as $sectionType) {
            $configData = $this->prepareStoreConfigFieldsetData($sectionType->getConfig(), $configData);
        }

        $this->data[$store->getId()] = array_merge(
            $configData,
            [ 'store_id' => $store->getId() ]
        );

        return $this->data;
    }
}
