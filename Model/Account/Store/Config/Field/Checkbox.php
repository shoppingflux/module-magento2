<?php

namespace ShoppingFeed\Manager\Model\Account\Store\Config\Field;

use Magento\Framework\Phrase;
use Magento\Ui\Component\Form\Element\Checkbox as UiCheckbox;
use Magento\Ui\Component\Form\Element\DataType\Boolean as UiBoolean;
use ShoppingFeed\Manager\Model\Account\Store\Config\AbstractField;
use ShoppingFeed\Manager\Model\Account\Store\Config\Value\Handler\Boolean as BooleanHandler;


class Checkbox extends AbstractField
{
    /**
     * @var bool
     */
    private $isCheckedByDefault;

    /**
     * @var Phrase|string
     */
    private $checkedNotice;

    /**
     * @var Phrase|string
     */
    private $uncheckedNotice;

    /**
     * @var string[]
     */
    private $checkedDependentFieldNames;

    /**
     * @var string[]
     */
    private $uncheckedDependentFieldNames;

    /**
     * @var string
     */
    private $checkedLabel;

    /**
     * @var string
     */
    private $uncheckedLabel;

    // @todo CheckboxOption object to hold: notice, label, dependent field names

    /**
     * @param string $name
     * @param string $label
     * @param bool $isCheckedByDefault
     * @param Phrase|string $checkedNotice
     * @param Phrase|string $uncheckedNotice
     * @param string[] $checkedDependentFieldNames
     * @param string[] $uncheckedDependentFieldNames
     * @param string $checkedLabel
     * @param string $uncheckedLabel
     */
    public function __construct(
        $name,
        $label,
        $isCheckedByDefault = false,
        $checkedNotice = '',
        $uncheckedNotice = '',
        array $checkedDependentFieldNames = [],
        array $uncheckedDependentFieldNames = [],
        $checkedLabel = '',
        $uncheckedLabel = ''
    ) {
        $this->isCheckedByDefault = (bool) $isCheckedByDefault;
        $this->checkedNotice = $checkedNotice;
        $this->uncheckedNotice = $uncheckedNotice;
        $this->checkedDependentFieldNames = $checkedDependentFieldNames;
        $this->uncheckedDependentFieldNames = $uncheckedDependentFieldNames;
        $this->checkedLabel = (string) trim($checkedLabel) ?? __('Yes');
        $this->uncheckedLabel = (string) trim($uncheckedLabel) ?? __('No');
        parent::__construct($name, new BooleanHandler(), $label, false, $isCheckedByDefault, $isCheckedByDefault);
    }

    public function getBaseUiMetaConfig()
    {
        $metaConfig = array_merge(
            parent::getBaseUiMetaConfig(),
            [
                'dataType' => UiBoolean::NAME,
                'formElement' => UiCheckbox::NAME,
                'prefer' => 'toggle',
                'valueMap' => [ 'true' => 1, 'false' => 0 ],
                'toggleLabels' => [ 'on' => $this->checkedLabel, 'off' => $this->uncheckedLabel ],
                'default' => $this->isCheckedByDefault ? 1 : 0,
            ]
        );

        if ($this->checkedNotice !== $this->uncheckedNotice) {
            if (!empty($this->checkedNotice) || !empty($this->uncheckedNotice)) {
                $metaConfig['component'] = 'Magento_Ui/js/form/element/single-checkbox-toggle-notice';
                $metaConfig['notices'] = [ 0 => (string) $this->uncheckedNotice, 1 => (string) $this->checkedNotice ];
            }
        } elseif (!empty($this->checkedNotice)) {
            $metaConfig['notice'] = (string) $this->checkedNotice;
        }

        $dependencies = [];

        if (!empty($this->checkedDependentFieldNames)) {
            $dependencies[] = [ 'field_names' => $this->checkedDependentFieldNames, 'values' => [ 1 ] ];
        }

        if (!empty($this->uncheckedDependentFieldNames)) {
            $dependencies[] = [ 'field_names' => $this->uncheckedDependentFieldNames, 'values' => [ 0 ] ];
        }

        if (!empty($dependencies)) {
            $metaConfig['switcherConfig'] = $this->getSwitcherConfig($dependencies, [ 0, 1 ]);
        }

        return $metaConfig;
    }
}
