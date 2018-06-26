<?php

namespace ShoppingFeed\Manager\Model\Config\Field\Category;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Ui\Component\Container as UiContainer;
use Magento\Ui\Component\Form\Element\Select as UiSelect;
use Magento\Ui\Component\Form\Field as UiField;
use ShoppingFeed\Manager\Model\Config\AbstractField;
use ShoppingFeed\Manager\Model\Config\Value\Handler\Integer as IntegerHandler;
use ShoppingFeed\Manager\Model\Config\Value\HandlerFactoryInterface as ValueHandlerFactoryInterface;


class MultiSelect extends AbstractField
{
    const TYPE_CODE = 'category_multi_select';

    /**
     * @var array
     */
    private $categoryTree;

    /**
     * @param ValueHandlerFactoryInterface $valueHandlerFactory
     * @param string $name
     * @param array $categoryTree
     * @param string $label
     * @param bool $isRequired
     * @param mixed|null $defaultFormValue
     * @param mixed|null $defaultUseValue
     * @param string $notice
     * @param int|null $sortOrder
     */
    public function __construct(
        ValueHandlerFactoryInterface $valueHandlerFactory,
        $name,
        array $categoryTree,
        $label,
        $isRequired = false,
        $defaultFormValue = null,
        $defaultUseValue = null,
        $notice = '',
        $sortOrder = null
    ) {
        $this->categoryTree = $categoryTree;

        parent::__construct(
            $name,
            $valueHandlerFactory->create(IntegerHandler::TYPE_CODE),
            $label,
            $isRequired,
            $defaultFormValue,
            $defaultUseValue,
            $notice,
            [],
            $sortOrder
        );
    }

    public function getBaseUiMetaConfig()
    {
        return [
            'componentType' => UiField::NAME,
            'component' => 'ShoppingFeed_Manager/js/form/element/ui-select',
            'formElement' => UiSelect::NAME,
            'elementTmpl' => 'ShoppingFeed_Manager/form/element/ui-select',
            'options' => $this->categoryTree,
            'multiple' => true,
            'filterOptions' => true,
            'chipsEnabled' => false,
            'disableLabel' => true,
            'showFilteredQuantity' => false,
            'levelsVisibility' => 3,
            'clearBtn' => true,
            'resetBtn' => true,
        ];
    }

    public function getUiMetaConfig()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => array_merge(
                        [
                            'label' => $this->getLabel(),
                            'componentType' => UiContainer::NAME,
                            'component' => 'Magento_Ui/js/form/components/group',
                            'template' => 'ShoppingFeed_Manager/form/group/group',
                            'dataScope' => '',
                            'breakLine' => false,
                        ],
                        array_filter([ 'sortOrder' => $this->getSortOrder() ])
                    ),
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
