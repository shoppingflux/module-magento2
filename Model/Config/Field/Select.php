<?php

namespace ShoppingFeed\Manager\Model\Config\Field;

use Magento\Framework\Phrase;
use Magento\Ui\Component\Form\Element\Select as UiSelect;
use ShoppingFeed\Manager\Model\Config\AbstractField;
use ShoppingFeed\Manager\Model\Config\Value\Handler\Option as OptionHandler;


/**
 * @method OptionHandler getValueHandler()
 */
class Select extends AbstractField
{
    const TYPE_CODE = 'select';

    /**
     * @var Dependency[]
     */
    private $dependencies;

    /**
     * @param DependencyFactory $dependencyFactory
     * @param string $name
     * @param OptionHandler $valueHandler
     * @param string $label
     * @param bool $isRequired
     * @param mixed|null $defaultFormValue
     * @param mixed|null $defaultUseValue
     * @param Phrase|string $notice
     * @param array $dependencies
     * @param int|null $sortOrder
     */
    public function __construct(
        DependencyFactory $dependencyFactory,
        $name,
        OptionHandler $valueHandler,
        $label,
        $isRequired = false,
        $defaultFormValue = null,
        $defaultUseValue = null,
        $notice = '',
        array $dependencies = [],
        $sortOrder = null
    ) {
        $this->dependencies = [];

        foreach ($dependencies as $dependency) {
            $this->dependencies[] = $dependencyFactory->create($dependency);
        }

        parent::__construct(
            $name,
            $valueHandler,
            $label,
            $isRequired,
            $defaultFormValue,
            $defaultUseValue,
            $notice,
            [],
            $sortOrder
        );
    }

    /**
     * @return mixed
     */
    protected function getEmptyOptionValue()
    {
        return '';
    }

    /**
     * @return Phrase|string
     */
    protected function getEmptyOptionLabel()
    {
        return $this->isRequired() ? __('Choose a Value') : __('None');
    }

    /**
     * @return array|false
     */
    protected function getEmptyOption()
    {
        return $this->getValueHandler()->hasEmptyOption()
            ? false
            : [ 'value' => $this->getEmptyOptionValue(), 'label' => $this->getEmptyOptionLabel() ];
    }

    public function getBaseUiMetaConfig()
    {
        $valueHandler = $this->getValueHandler();
        $options = $valueHandler->getOptionArray();
        $optionValues = $valueHandler->getOptionValues();

        if (!$valueHandler->hasEmptyOption()) {
            $optionValues[] = $this->getEmptyOptionValue();
            array_unshift($options, $this->getEmptyOption());
        }

        $metaConfig = array_merge(
            parent::getBaseUiMetaConfig(),
            [
                'formElement' => UiSelect::NAME,
                'options' => $options,
            ]
        );

        if (!empty($this->dependencies)) {
            $metaConfig['switcherConfig'] = $this->getSwitcherConfig($this->dependencies, $optionValues);
        }

        return $metaConfig;
    }
}
