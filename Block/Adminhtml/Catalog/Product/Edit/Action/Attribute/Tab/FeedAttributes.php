<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Catalog\Product\Edit\Action\Attribute\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Catalog\Block\Adminhtml\Product\Edit\Action\Attribute\Tab\Attributes as AttributesTab;
use Magento\Catalog\Helper\Product\Edit\Action\Attribute as AttributeActionHelper;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\Form\Element\AbstractElement as AbstractFormElement;
use Magento\Framework\Data\Form\Element\Fieldset;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Category\SelectorInterface as CategorySelectorInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\Collection as StoreCollection;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\CollectionFactory as StoreCollectionFactory;
use ShoppingFeed\Manager\Model\StringHelper;

class FeedAttributes extends AttributesTab implements TabInterface
{
    const DATA_SCOPE = 'sfm_feed_attributes';

    const FIELD_IS_SELECTED = 'is_selected';
    const FIELD_SELECTED_CATEGORY_ID = 'selected_category_id';

    /**
     * @var StringHelper
     */
    private $stringHelper;

    /**
     * @var CategorySelectorInterface
     */
    private $categorySelector;

    /**
     * @var StoreCollectionFactory
     */
    private $storeCollectionFactory;

    /**
     * @var StoreCollection|null
     */
    private $storeCollection = null;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param ProductFactory $productFactory
     * @param AttributeActionHelper $attributeAction
     * @param StringHelper $stringHelper
     * @param CategorySelectorInterface $categorySelector
     * @param StoreCollectionFactory $storeCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        ProductFactory $productFactory,
        AttributeActionHelper $attributeAction,
        StringHelper $stringHelper,
        CategorySelectorInterface $categorySelector,
        StoreCollectionFactory $storeCollectionFactory,
        array $data = []
    ) {
        $this->stringHelper = $stringHelper;
        $this->categorySelector = $categorySelector;
        $this->storeCollectionFactory = $storeCollectionFactory;
        parent::__construct($context, $registry, $formFactory, $productFactory, $attributeAction, $data);
    }

    /**
     * @return StoreCollection
     */
    private function getStoreCollection()
    {
        if (null === $this->storeCollection) {
            $this->storeCollection = $this->storeCollectionFactory->create();
            $baseStoreId = $this->_attributeAction->getSelectedStoreId();

            if (!empty($baseStoreId)) {
                $this->storeCollection->addBaseStoreFilter($baseStoreId);
            }

            $this->storeCollection->load();
        }

        return $this->storeCollection;
    }

    /**
     * @param StoreInterface $store
     * @param array|null $tree
     * @param int $level
     * @return array
     */
    private function getStoreCategoryOptions(StoreInterface $store, $tree = null, $level = 0)
    {
        $options = [];

        if (!is_array($tree)) {
            $tree = $this->categorySelector->getStoreCategoryTree($store);
        }

        usort(
            $tree,
            function (array $optionA, array $optionB) {
                return $this->stringHelper->strnatcmp($optionA['label'], $optionB['label']);
            }
        );

        foreach ($tree as $node) {
            $options[] = [
                'value' => $node['value'],
                'label' => str_repeat('-', $level * 4) . ' ' . $node['label'],
            ];

            if (!empty($node['optgroup'])) {
                $options = array_merge(
                    $options,
                    $this->getStoreCategoryOptions($store, $node['optgroup'], $level + 1)
                );
            }
        }

        return $options;
    }

    /**
     * @param Fieldset $fieldset
     * @param string $name
     * @param string $type
     * @param array $config
     * @param StoreInterface $store
     * @return AbstractFormElement
     */
    private function addFieldToFieldset(Fieldset $fieldset, $name, $type, array $config, StoreInterface $store)
    {
        $form = $fieldset->getForm();

        $field = $fieldset->addField(
            $name . '_' . $store->getId(),
            $type,
            array_merge(
                $config,
                [ 'name' => $form->addSuffixToName($name, static::DATA_SCOPE . '[' . $store->getId() . ']') ]
            )
        );

        $field->setAfterElementHtml($this->_getAdditionalElementHtml($field));

        return $field;
    }

    protected function _beforeToHtml()
    {
        // We can't override _prepareForm() here due to the return type declaration that was arbitrarily added by:
        // https://github.com/magento/magento2/commit/aa183e0796e95bf2594e2fafd6c4b32b581d800c

        /** @var Form $form */
        $form = $this->_formFactory->create();

        $storeCollection = $this->getStoreCollection();
        $isSingleStoreMode = ($storeCollection->count() === 1);

        /** @var StoreInterface $store */
        foreach ($storeCollection as $store) {
            $fieldset = $form->addFieldset(
                static::DATA_SCOPE . '_' . $store->getId(),
                [ 'legend' => $isSingleStoreMode ? null : __('Store: %1 - Feed State', $store->getShoppingFeedName()) ]
            );

            $this->addFieldToFieldset(
                $fieldset,
                static::FIELD_IS_SELECTED,
                'select',
                [
                    'label' => __('Selected'),
                    'required' => true,
                    'values' => [ __('No'), __('Yes'), ],
                ],
                $store
            );

            $categoryOptions = $this->getStoreCategoryOptions($store);

            array_unshift(
                $categoryOptions,
                [
                    'value' => '',
                    'label' => '',
                ]
            );

            $this->addFieldToFieldset(
                $fieldset,
                static::FIELD_SELECTED_CATEGORY_ID,
                'select',
                [
                    'label' => __('Forced Category'),
                    'required' => false,
                    'values' => $categoryOptions,
                ],
                $store
            );
        }

        $this->setForm($form);
        $this->_initFormValues();

        return Widget::_beforeToHtml();
    }

    public function getTabLabel()
    {
        return __('Shopping Feed');
    }

    public function getTabTitle()
    {
        return __('Shopping Feed');
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        return false;
    }
}
