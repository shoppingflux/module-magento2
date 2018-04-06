<?php

namespace ShoppingFeed\Manager\Model\Account\Store\Config\Field\Category;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Ui\Component\Container as UiContainer;
use Magento\Ui\Component\Form\Element\Select as UiSelect;
use Magento\Ui\Component\Form\Field as UiField;
use ShoppingFeed\Manager\Model\Account\Store\Config\AbstractField;
use ShoppingFeed\Manager\Model\Account\Store\Config\Value\Handler\Integer as IntegerHandler;


class MultiSelect extends AbstractField
{
    /**
     * @var array
     */
    private $categoryTree;

    /**
     * @param string $name
     * @param CategoryCollection $categoryCollection
     * @param string $label
     * @param bool $isRequired
     * @param mixed|null $defaultFormValue
     * @param mixed|null $defaultUseValue
     * @param string $notice
     */
    public function __construct(
        $name,
        CategoryCollection $categoryCollection,
        $label,
        $isRequired = false,
        $defaultFormValue = null,
        $defaultUseValue = null,
        $notice = ''
    ) {
        $categoryTree = [];

        foreach ($categoryCollection as $category) {
            $categoryId = (int) $category->getId();
            $parentId = (int) $category->getParentId();

            if (!isset($categoryTree[$categoryId])) {
                $categoryTree[$categoryId] = [ 'value' => $categoryId ];
            }

            if (!isset($categoryTree[$parentId])) {
                $categoryTree[$parentId] = [ 'value' => $parentId ];
            }

            $categoryTree[$categoryId]['is_active'] = $category->getIsActive();
            $categoryTree[$categoryId]['label'] = $category->getName();
            $categoryTree[$parentId]['optgroup'][] = &$categoryTree[$categoryId];
        }

        $this->categoryTree = $categoryTree[Category::TREE_ROOT_ID]['optgroup'];

        parent::__construct(
            $name,
            new IntegerHandler(),
            $label,
            $isRequired,
            $defaultFormValue,
            $defaultUseValue,
            $notice,
            []
        );
    }

    public function getBaseUiMetaConfig()
    {
        return [
            'componentType' => UiField::NAME,
            'formElement' => UiSelect::NAME,
            'elementTmpl' => 'ui/grid/filters/elements/ui-select',
            'component' => 'Magento_Ui/js/form/element/ui-select',
            'options' => $this->categoryTree,
            'filterOptions' => true,
            'chipsEnabled' => false,
            'disableLabel' => true,
            'showFilteredQuantity' => false,
            'levelsVisibility' => 3,
        ];
    }

    public function getUiMetaConfig()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => $this->getLabel(),
                        'componentType' => UiContainer::NAME,
                        'component' => 'Magento_Ui/js/form/components/group',
                        'template' => 'ShoppingFeed_Manager/form/group/group',
                        'dataScope' => '',
                        'breakLine' => false,
                    ],
                ],
            ],
            'children' => [
                $this->getName() => parent::getUiMetaConfig(),
            ],
        ];
    }

    /**
     * @param mixed $value
     * @param mixed $defaultValue
     * @param string $handlerPrepareMethod
     * @param array $handlerMethodAdditionalArguments
     * @return array
     */
    protected function prepareRawValue(
        $value,
        $defaultValue,
        $handlerPrepareMethod,
        array $handlerMethodAdditionalArguments
    ) {
        $valueHandler = $this->getValueHandler();

        if (!is_array($value)) {
            $value = is_array($defaultValue) ? $defaultValue : [];
        }

        foreach ($value as $key => $subValue) {
            $arguments = $handlerMethodAdditionalArguments;
            array_unshift($arguments, $subValue);
            $subValue = call_user_func_array([ $valueHandler, $handlerPrepareMethod ], $arguments);

            if ((null !== $subValue) && (Category::TREE_ROOT_ID !== $subValue)) {
                $value[$key] = $subValue;
            } else {
                unset($value[$key]);
            }
        }

        return $value;
    }

    public function prepareRawValueForForm($value)
    {
        return $this->prepareRawValue($value, [], 'prepareRawValueForForm', [ null, $this->isRequired() ]);
    }

    public function prepareRawValueForUse($value)
    {
        return $this->prepareRawValue(
            $value,
            $this->getDefaultUseValue(),
            'prepareRawValueForUse',
            [ null, $this->isRequired() ]
        );
    }

    public function prepareFormValueForSave($value)
    {
        return $this->prepareRawValue($value, [], 'prepareFormValueForSave', [ $this->isRequired() ]);
    }
}
