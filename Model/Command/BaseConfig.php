<?php

namespace ShoppingFeed\Manager\Model\Command;

use Magento\Framework\DataObject;
use Magento\Ui\Component\Form\Element\DataType\Text as UiText;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Config\Basic\AbstractConfig;
use ShoppingFeed\Manager\Model\Config\Field\Checkbox;
use ShoppingFeed\Manager\Model\Config\Field\MultiSelect;
use ShoppingFeed\Manager\Model\Config\FieldFactoryInterface;
use ShoppingFeed\Manager\Model\Config\Value\Handler\Option as OptionHandler;
use ShoppingFeed\Manager\Model\Config\Value\HandlerFactoryInterface as ValueHandlerFactoryInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\CollectionFactory as StoreCollectionFactory;

class BaseConfig extends AbstractConfig implements ConfigInterface
{
    const KEY_APPLY_TO_ALL_STORES = 'apply_to_all_stores';
    const KEY_APPLY_TO_STORE_IDS = 'apply_to_store_ids';

    /**
     * @var StoreCollectionFactory
     */
    private $storeCollectionFactory;

    /**
     * @var StoreInterface[]|null
     */
    private $allStores = null;

    /**
     * @param FieldFactoryInterface $fieldFactory
     * @param ValueHandlerFactoryInterface $valueHandlerFactory
     * @param StoreCollectionFactory $storeCollectionFactory
     */
    public function __construct(
        FieldFactoryInterface $fieldFactory,
        ValueHandlerFactoryInterface $valueHandlerFactory,
        StoreCollectionFactory $storeCollectionFactory
    ) {
        $this->storeCollectionFactory = $storeCollectionFactory;
        parent::__construct($fieldFactory, $valueHandlerFactory);
    }

    /**
     * @return StoreInterface[]
     */
    private function getAllStores()
    {
        if (null === $this->allStores) {
            $this->allStores = $this->storeCollectionFactory->create()->getItems();
        }

        return $this->allStores;
    }

    public function isAppliableByStore()
    {
        return true;
    }

    protected function getBaseFields()
    {
        if (!$this->isAppliableByStore()) {
            return [];
        }

        $stores = $this->getAllStores();
        $storeOptions = [];

        foreach ($stores as $store) {
            $storeOptions[] = [
                'value' => $store->getId(),
                'label' => $store->getShoppingFeedName(),
            ];
        }

        return [
            $this->fieldFactory->create(
                Checkbox::TYPE_CODE,
                [
                    'name' => self::KEY_APPLY_TO_ALL_STORES,
                    'isRequired' => true,
                    'isCheckedByDefault' => true,
                    'label' => __('Apply to All Stores'),
                    'sortOrder' => 100010,
                    'uncheckedDependentFieldNames' => [ self::KEY_APPLY_TO_STORE_IDS ],
                ]
            ),

            $this->fieldFactory->create(
                MultiSelect::TYPE_CODE,
                [
                    'name' => self::KEY_APPLY_TO_STORE_IDS,
                    'valueHandler' => $this->valueHandlerFactory->create(
                        OptionHandler::TYPE_CODE,
                        [
                            'dataType' => UiText::NAME,
                            'optionArray' => $storeOptions,
                        ]
                    ),
                    'isRequired' => true,
                    'label' => __('Apply to Stores'),
                    'sortOrder' => 100020,
                ]
            ),
        ];
    }

    public function getStoreIds(DataObject $configData)
    {
        if (!$this->isAppliableByStore()) {
            return [];
        }

        return $this->getFieldValue(self::KEY_APPLY_TO_ALL_STORES, $configData)
            ? array_keys($this->getAllStores())
            : $this->getFieldValue(self::KEY_APPLY_TO_STORE_IDS, $configData);
    }

    public function getStores(DataObject $configData)
    {
        if (!$this->isAppliableByStore()) {
            return [];
        }

        $stores = $this->getAllStores();

        if (is_array($storeIds = $this->getStoreIds($configData))) {
            $stores = array_intersect_key($stores, array_flip($storeIds));
        }

        return $stores;
    }
}
