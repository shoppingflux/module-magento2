<?php

namespace ShoppingFeed\Manager\Model\Config\Field;

use Magento\Framework\Phrase;
use Magento\Ui\Component\Form\Element\Checkbox as UiCheckbox;
use Magento\Ui\Component\Form\Element\DataType\Boolean as UiBoolean;
use ShoppingFeed\Manager\Model\Config\AbstractField;
use ShoppingFeed\Manager\Model\Config\Value\Handler\Boolean as BooleanHandler;
use ShoppingFeed\Manager\Model\Config\Value\HandlerFactoryInterface as ValueHandlerFactoryInterface;

class Checkbox extends AbstractField
{
    const TYPE_CODE = 'checkbox';

    /**
     * @var bool
     */
    private $isCheckedByDefault;

    /**
     * @var string
     */
    private $checkedLabel;

    /**
     * @var string
     */
    private $uncheckedLabel;

    /**
     * @var Phrase|string
     */
    private $checkedNotice;

    /**
     * @var Phrase|string
     */
    private $uncheckedNotice;

    /**
     * @var Dependency[]
     */
    private $dependencies;

    /**
     * @param DependencyFactory $dependencyFactory
     * @param ValueHandlerFactoryInterface $valueHandlerFactory
     * @param string $name
     * @param string $label
     * @param bool $isCheckedByDefault
     * @param string $checkedLabel
     * @param string $uncheckedLabel
     * @param string $checkedNotice
     * @param string $uncheckedNotice
     * @param array $checkedDependentFieldNames
     * @param array $uncheckedDependentFieldNames
     * @param int|null $sortOrder
     */
    public function __construct(
        DependencyFactory $dependencyFactory,
        ValueHandlerFactoryInterface $valueHandlerFactory,
        $name,
        $label,
        $isCheckedByDefault = false,
        $checkedLabel = '',
        $uncheckedLabel = '',
        $checkedNotice = '',
        $uncheckedNotice = '',
        array $checkedDependentFieldNames = [],
        array $uncheckedDependentFieldNames = [],
        $sortOrder = null
    ) {
        $this->isCheckedByDefault = (bool) $isCheckedByDefault;
        $this->checkedLabel = (string) trim($checkedLabel) ?? __('Yes');
        $this->uncheckedLabel = (string) trim($uncheckedLabel) ?? __('No');
        $this->checkedNotice = (string) $checkedNotice;
        $this->uncheckedNotice = (string) $uncheckedNotice;
        $this->dependencies = [];

        if (!empty($checkedDependentFieldNames)) {
            $this->dependencies[] = $dependencyFactory->create(
                [
                    'values' => [ 1 ],
                    'fieldNames' => $checkedDependentFieldNames,
                ]
            );
        }

        if (!empty($uncheckedDependentFieldNames)) {
            $this->dependencies[] = $dependencyFactory->create(
                [
                    'values' => [ 0 ],
                    'fieldNames' => $uncheckedDependentFieldNames,
                ]
            );
        }

        $booleanHandler = $valueHandlerFactory->create(BooleanHandler::TYPE_CODE);

        parent::__construct(
            $name,
            $booleanHandler,
            $label,
            false,
            $isCheckedByDefault,
            $isCheckedByDefault,
            '',
            [],
            $sortOrder
        );
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

        if (!empty($this->dependencies)) {
            $metaConfig['switcherConfig'] = $this->getSwitcherConfig($this->dependencies, [ 0, 1 ]);
        }

        return $metaConfig;
    }
}
