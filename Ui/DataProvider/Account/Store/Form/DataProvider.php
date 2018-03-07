<?php

namespace ShoppingFeed\Manager\Ui\DataProvider\Account\Store\Form;

use Magento\Ui\Component\Form\Fieldset as FormFieldset;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Account\Store\ConfigInterface as StoreConfigInterface;
use ShoppingFeed\Manager\Model\Account\Store\RegistryConstants;
use ShoppingFeed\Manager\Ui\DataProvider\Account\Store\AbstractDataProvider;


class DataProvider extends AbstractDataProvider
{

    /**
     * @param StoreConfigInterface $config
     * @param string $fieldsetName
     * @return array
     */
    private function getConfigFormFieldsetMeta(StoreConfigInterface $config, $fieldsetName)
    {
        $dataScope = $config->getScope();

        foreach ($config->getScopeSubPath() as $subScope) {
            $dataScope .= '.' . $subScope;
        }

        $childrenMeta = [];

        foreach ($config->getFields() as $fieldName => $field) {
            $childrenMeta[$fieldName] = $field->getMeta();
        }

        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => 'fieldset',
                        'collapsible' => true,
                        'dataScope' => $dataScope,
                        'label' => $config->getFieldsetLabel(),
                    ],
                    'js_config' => [ 'component' => 'Magento_Ui/js/form/components/fieldset' ],
                ],
            ],
            'attributes' => [
                'class' => FormFieldset::class,
                'name' => $fieldsetName,
            ],
            'children' => $childrenMeta,
        ];
    }

    public function getMeta()
    {
        $this->meta['feed_export_state'] = $this->getConfigFormFieldsetMeta(
            $this->exportStateConfig,
            'feed_export_state'
        );

        foreach ($this->sectionTypePool->getSortedTypes() as $sectionType) {
            $fieldsetName = 'feed_section_' . $sectionType->getCode();
            $this->meta[$fieldsetName] = $this->getConfigFormFieldsetMeta($sectionType->getConfig(), $fieldsetName);
        }

        return $this->meta;
    }

    /**
     * @param StoreConfigInterface $config
     * @param array $data
     * @return array
     */
    private function prepareConfigFormFieldsetData(StoreConfigInterface $config, array $data)
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
        $configData = $this->prepareConfigFormFieldsetData($this->exportStateConfig, $configData);

        foreach ($this->sectionTypePool->getSortedTypes() as $sectionType) {
            $configData = $this->prepareConfigFormFieldsetData($sectionType->getConfig(), $configData);
        }

        $this->data[$store->getId()] = array_merge(
            $configData,
            [ 'store_id' => $store->getId() ]
        );

        return $this->data;
    }
}
