<?php

namespace ShoppingFeed\Manager\Model\Config\Value\Handler;

use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Model\Config\AbstractField;
use ShoppingFeed\Manager\Model\Config\Value\AbstractHandler;

class Rows extends AbstractHandler
{
    const TYPE_CODE = 'rows';

    /**
     * @var AbstractField[]
     */
    private $fields;

    /**
     * @var string
     */
    private $rowIdPropertyName;

    /**
     * @var string
     */
    private $isDeletedRowPropertyName;

    /**
     * @param array $fields
     * @param string $rowIdPropertyName
     * @param string $isDeletedPropertyName
     * @throws LocalizedException
     */
    public function __construct(
        array $fields,
        $rowIdPropertyName = 'record_id',
        $isDeletedPropertyName = 'delete'
    ) {
        if (empty($fields)) {
            throw new LocalizedException(__('Rows handler must contain at least one field.'));
        }

        foreach ($fields as $field) {
            if (!$field instanceof AbstractField) {
                throw new LocalizedException(
                    __('Rows handler must only contain fields of type: %1.', AbstractField::class)
                );
            }
        }

        $this->fields = $fields;
        $this->rowIdPropertyName = trim($rowIdPropertyName);
        $this->isDeletedRowPropertyName = trim($isDeletedPropertyName);
    }

    public function getFormDataType()
    {
        return null;
    }

    public function isUndefinedValue($value)
    {
        return !is_array($value);
    }

    protected function isValidValue($value, $isRequired)
    {
        return !$isRequired || is_array($value) && !empty($value);
    }

    public function prepareRawValueForForm($value, $defaultValue, $isRequired)
    {
        $rows = [];

        if (!is_array($value) && is_array($defaultValue)) {
            $value = $defaultValue;
        }

        if (is_array($value)) {
            $index = 1;

            foreach ($value as $key => $rowData) {
                $row = [];

                foreach ($this->fields as $field) {
                    $fieldName = $field->getName();

                    if (!isset($rowData[$fieldName])) {
                        continue 2;
                    }

                    $row[$fieldName] = $field->getValueHandler()
                        ->prepareRawValueForForm(
                            $rowData[$fieldName],
                            $field->getDefaultFormValue(),
                            $field->isRequired()
                        );
                }

                $row[$this->rowIdPropertyName] = $index++;
                $rows[] = $row;
            }
        }

        return $rows;
    }

    public function prepareRawValueForUse($value, $defaultValue, $isRequired)
    {
        $rows = [];

        if (!is_array($value) && is_array($defaultValue)) {
            $value = $defaultValue;
        }

        if (is_array($value)) {
            foreach ($value as $key => $rowData) {
                $row = [];

                foreach ($this->fields as $field) {
                    $fieldName = $field->getName();

                    if (!isset($rowData[$fieldName])) {
                        continue 2;
                    }

                    $row[$fieldName] = $field->getValueHandler()
                        ->prepareRawValueForUse(
                            $rowData[$fieldName],
                            $field->getDefaultUseValue(),
                            $field->isRequired()
                        );
                }

                $rows[] = $row;
            }
        }

        return $rows;
    }

    public function prepareFormValueForSave($value, $isRequired)
    {
        $rows = [];

        if (is_array($value)) {
            foreach ($value as $key => $rowData) {
                if (is_array($rowData)) {
                    if (isset($rowData[$this->isDeletedRowPropertyName]) && $rowData[$this->isDeletedRowPropertyName]) {
                        continue;
                    }

                    $row = [];

                    foreach ($this->fields as $field) {
                        $fieldName = $field->getName();

                        if (!isset($rowData[$fieldName])) {
                            continue 2;
                        }

                        $row[$fieldName] = $field->getValueHandler()
                            ->prepareFormValueForSave(
                                $rowData[$fieldName],
                                $field->isRequired()
                            );
                    }

                    $rows[] = $row;
                }
            }
        }

        return $isRequired && empty($rows) ? null : $rows;
    }
}
