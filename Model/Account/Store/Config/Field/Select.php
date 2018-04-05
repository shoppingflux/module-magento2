<?php

namespace ShoppingFeed\Manager\Model\Account\Store\Config\Field;

use Magento\Framework\Phrase;
use Magento\Ui\Component\Form\Element\Select as UiSelect;
use ShoppingFeed\Manager\Model\Account\Store\Config\AbstractField;
use ShoppingFeed\Manager\Model\Account\Store\Config\Value\Handler\Option as OptionHandler;


/**
 * @method OptionHandler getValueHandler()
 */
class Select extends AbstractField
{
    /**
     * @var array
     */
    private $dependencies;

    /**
     * @param string $name
     * @param OptionHandler $valueHandler
     * @param string $label
     * @param bool $isRequired
     * @param mixed|null $defaultFormValue
     * @param mixed|null $defaultUseValue
     * @param Phrase|string $notice
     * @param array $dependencies
     */
    public function __construct(
        $name,
        OptionHandler $valueHandler,
        $label,
        $isRequired = false,
        $defaultFormValue = null,
        $defaultUseValue = null,
        $notice = '',
        array $dependencies = []
    ) {
        $this->dependencies = $dependencies;

        parent::__construct(
            $name,
            $valueHandler,
            $label,
            $isRequired,
            $defaultFormValue,
            $defaultUseValue,
            $notice
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
        $options = $this->getValueHandler()->getOptionArray();
        $emptyOption = $this->getEmptyOption();

        if (is_array($emptyOption)) {
            array_unshift($options, $emptyOption);
        }

        $metaConfig = array_merge(
            parent::getBaseUiMetaConfig(),
            [
                'formElement' => UiSelect::NAME,
                'options' => $options,
            ]
        );

        if (!empty($this->dependencies)) {
            $metaConfig['switcherConfig'] = $this->getSwitcherConfig(
                $this->dependencies,
                $this->getValueHandler()->getOptionValues()
            );
        }

        return $metaConfig;
    }
}
