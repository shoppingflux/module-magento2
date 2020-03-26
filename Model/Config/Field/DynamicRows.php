<?php

namespace ShoppingFeed\Manager\Model\Config\Field;

use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\Container as UiContainer;
use Magento\Ui\Component\DynamicRows as UiDynamicRows;
use Magento\Ui\Component\Form\Element\ActionDelete as UiDeleteAction;
use ShoppingFeed\Manager\Model\Config\AbstractField;
use ShoppingFeed\Manager\Model\Config\Value\Handler\Rows as RowsHandler;
use ShoppingFeed\Manager\Model\Config\Value\HandlerFactoryInterface as ValueHandlerFactoryInterface;

class DynamicRows extends AbstractField
{
    const TYPE_CODE = 'dynamic_rows';

    /**
     * @var AbstractField[] $fields
     */
    private $fields;

    /**
     * @var bool
     */
    private $sortableRows;

    /**
     * @var bool
     */
    private $defaultRow;

    /**
     * @var int
     */
    private $pageSize;

    /**
     * @var bool
     */
    private $showColumnHeaders;

    /**
     * @var string
     */
    private $addButtonLabel;

    /**
     * @param ValueHandlerFactoryInterface $valueHandlerFactory
     * @param AbstractField[] $fields
     * @param string $name
     * @param string $label
     * @param mixed|null $defaultFormValue
     * @param mixed|null $defaultUseValue
     * @param int|null $sortOrder
     * @param bool $sortableRows
     * @param bool $defaultRow
     * @param int $pageSize
     * @param bool $showColumnHeaders
     * @param string|null $addButtonLabel
     * @throws LocalizedException
     */
    public function __construct(
        ValueHandlerFactoryInterface $valueHandlerFactory,
        array $fields,
        $name,
        $label,
        $defaultFormValue = null,
        $defaultUseValue = null,
        $sortOrder = null,
        $sortableRows = false,
        $defaultRow = false,
        $pageSize = 20,
        $showColumnHeaders = true,
        $addButtonLabel = null
    ) {
        $this->fields = [];

        if (empty($fields)) {
            throw new LocalizedException(__('Dynamic rows must contain at least one field (name: %1).', $name));
        }

        foreach ($fields as $field) {
            if (!$field instanceof AbstractField) {
                throw new LocalizedException(
                    __('Dynamic rows must only contain fields of type: %1 (name: "%2").', AbstractField::class, $name)
                );
            }
        }

        $this->fields = $fields;
        $this->sortableRows = (bool) $sortableRows;
        $this->defaultRow = (bool) $defaultRow;
        $this->pageSize = max(1, (int) $pageSize);
        $this->showColumnHeaders = (bool) $showColumnHeaders;
        $this->addButtonLabel = (null === $addButtonLabel) ? __('Add') : (string) $addButtonLabel;

        parent::__construct(
            $name,
            $valueHandlerFactory->create(RowsHandler::TYPE_CODE, [ 'fields' => $fields ]),
            $label,
            false,
            $defaultFormValue,
            $defaultUseValue,
            '',
            [],
            $sortOrder
        );
    }

    public function getUiMetaConfig()
    {
        $childrenMetaConfig = [];

        foreach ($this->fields as $field) {
            $childrenMetaConfig[$field->getName()] = $field->getUiMetaConfig();
        }

        return [
            'arguments' => [
                'data' => [
                    'config' => array_merge(
                        [
                            'label' => $this->getLabel(),
                            'componentType' => UiDynamicRows::NAME,
                            'component' => 'ShoppingFeed_Manager/js/form/dynamic-rows/dynamic-rows',
                            'addButtonLabel' => $this->addButtonLabel,
                            'defaultRecord' => $this->defaultRow,
                            'columnsHeader' => $this->showColumnHeaders,
                            'pageSize' => $this->pageSize,
                        ],
                        array_filter(
                            [
                                'sortOrder' => $this->getSortOrder(),
                                'dndConfig' => $this->sortableRows ? null : '{}',
                            ]
                        )
                    ),
                ],
            ],
            'children' => [
                'record' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'componentType' => UiContainer::NAME,
                                'component' => 'Magento_Ui/js/dynamic-rows/record',
                                'isTemplate' => true,
                                'visible' => true,
                            ],
                        ],
                    ],
                    'children' => array_merge(
                        $childrenMetaConfig,
                        [
                            '__delete_button__' => [
                                'arguments' => [
                                    'data' => [
                                        'config' => [
                                            'componentType' => UiDeleteAction::NAME,
                                            'template' => 'Magento_Backend/dynamic-rows/cells/action-delete',
                                        ],
                                    ],
                                ],
                            ],
                        ]
                    ),
                ],
            ],
        ];
    }
}
