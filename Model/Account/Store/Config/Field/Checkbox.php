<?php

namespace ShoppingFeed\Manager\Model\Account\Store\Config\Field;

use Magento\Framework\Phrase;
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
     * @param string $name
     * @param string $label
     * @param bool $isCheckedByDefault
     * @param Phrase|string $checkedNotice
     * @param Phrase|string $uncheckedNotice
     * @param string[] $checkedDependentFieldNames
     * @param string[] $uncheckedDependentFieldNames
     */
    public function __construct(
        string $name,
        $label,
        $isCheckedByDefault = false,
        $checkedNotice = '',
        $uncheckedNotice = '',
        array $checkedDependentFieldNames = [],
        array $uncheckedDependentFieldNames = []
    ) {
        $this->isCheckedByDefault = (bool) $isCheckedByDefault;
        $this->checkedNotice = $checkedNotice;
        $this->uncheckedNotice = $uncheckedNotice;
        $this->checkedDependentFieldNames = $checkedDependentFieldNames;
        $this->uncheckedDependentFieldNames = $uncheckedDependentFieldNames;
        parent::__construct($name, new BooleanHandler(), $label, false, $isCheckedByDefault, $isCheckedByDefault);
    }

    public function getMetaConfig()
    {
        $metaConfig = array_merge(
            parent::getMetaConfig(),
            [
                'dataType' => 'boolean',
                'formElement' => 'checkbox',
                'prefer' => 'toggle',
                'valueMap' => [ 'true' => 1, 'false' => 0 ],
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
