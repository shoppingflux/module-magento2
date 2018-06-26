<?php

namespace ShoppingFeed\Manager\Model\Shipping\Method\Applier;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Model\Config\FieldFactoryInterface;
use ShoppingFeed\Manager\Model\Config\FieldInterface;
use ShoppingFeed\Manager\Model\Config\Field\Checkbox;
use ShoppingFeed\Manager\Model\Config\Field\TextBox;
use ShoppingFeed\Manager\Model\Config\Value\HandlerFactoryInterface as ValueHandlerFactoryInterface;
use ShoppingFeed\Manager\Model\Config\Value\Handler\Text as TextHandler;


abstract class AbstractConfig implements ConfigInterface
{
    const KEY_ONLY_APPLY_IF_AVAILABLE = 'only_apply_if_available';
    const KEY_DEFAULT_CARRIER_TITLE = 'default_carrier_title';
    const KEY_FORCE_DEFAULT_CARRIER_TITLE = 'force_default_carrier_title';
    const KEY_DEFAULT_METHOD_TITLE = 'default_method_title';
    const KEY_FORCE_DEFAULT_METHOD_TITLE = 'force_default_method_title';

    /**
     * @var FieldFactoryInterface
     */
    protected $fieldFactory;

    /**
     * @var ValueHandlerFactoryInterface
     */
    protected $valueHandlerFactory;

    /**
     * @var FieldInterface[]
     */
    private $fields;

    /**
     * @param FieldFactoryInterface $fieldFactory
     * @param ValueHandlerFactoryInterface $valueHandlerFactory
     */
    public function __construct(FieldFactoryInterface $fieldFactory, ValueHandlerFactoryInterface $valueHandlerFactory)
    {
        $this->fieldFactory = $fieldFactory;
        $this->valueHandlerFactory = $valueHandlerFactory;
    }

    /**
     * @return FieldInterface[]
     */
    protected function getBaseFields()
    {
        return [
            $this->fieldFactory->create(
                Checkbox::TYPE_CODE,
                [
                    'name' => self::KEY_ONLY_APPLY_IF_AVAILABLE,
                    'isRequired' => true,
                    'label' => __('Only Apply if Available'),
                    'sortOrder' => 100010,
                ]
            ),

            $this->fieldFactory->create(
                TextBox::TYPE_CODE,
                [
                    'name' => self::KEY_DEFAULT_CARRIER_TITLE,
                    'valueHandler' => $this->valueHandlerFactory->create(TextHandler::TYPE_CODE),
                    'isRequired' => true,
                    'defaultFormValue' => $this->getBaseDefaultCarrierTitle(),
                    'defaultUseValue' => $this->getBaseDefaultCarrierTitle(),
                    'label' => __('Default Carrier Title'),
                    'sortOrder' => 100020,
                ]
            ),

            $this->fieldFactory->create(
                Checkbox::TYPE_CODE,
                [
                    'name' => self::KEY_FORCE_DEFAULT_CARRIER_TITLE,
                    'isRequired' => true,
                    'label' => __('Force Default Carrier Title'),
                    'sortOrder' => 100030,
                ]
            ),

            $this->fieldFactory->create(
                TextBox::TYPE_CODE,
                [
                    'name' => self::KEY_DEFAULT_METHOD_TITLE,
                    'valueHandler' => $this->valueHandlerFactory->create(TextHandler::TYPE_CODE),
                    'isRequired' => true,
                    'defaultFormValue' => $this->getBaseDefaultMethodTitle(),
                    'defaultUseValue' => $this->getBaseDefaultMethodTitle(),
                    'label' => __('Default Method Title'),
                    'sortOrder' => 100040,
                ]
            ),

            $this->fieldFactory->create(
                Checkbox::TYPE_CODE,
                [
                    'name' => self::KEY_FORCE_DEFAULT_METHOD_TITLE,
                    'isRequired' => true,
                    'label' => __('Force Default Method Title'),
                    'sortOrder' => 100050,
                ]
            ),
        ];
    }

    /**
     * @param mixed $field
     * @throws LocalizedException
     */
    private function checkFieldValidity($field)
    {
        if (!$field instanceof FieldInterface) {
            throw new LocalizedException(
                __(
                    'Config fields must be of type: ShoppingFeed\Manager\Model\Config\FieldInterface.'
                )
            );
        }
    }

    /**
     * @return FieldInterface[]
     * @throws LocalizedException
     */
    final public function getFields()
    {
        if (!is_array($this->fields)) {
            $this->fields = [];
            $baseFields = $this->getBaseFields();

            foreach ($baseFields as $field) {
                $this->checkFieldValidity($field);
                $this->fields[$field->getName()] = $field;
            }
        }

        return $this->fields;
    }

    final public function getField($name)
    {
        $fields = $this->getFields();
        return isset($fields[$name]) ? $fields[$name] : null;
    }

    /**
     * @param string $name
     * @param DataObject $configData
     * @return mixed|null
     */
    protected function getFieldValue($name, DataObject $configData)
    {
        $field = $this->getField($name);

        if (null === $field) {
            return null;
        }

        return !$configData->hasData($name)
            ? $field->getDefaultUseValue()
            : $field->prepareRawValueForUse($configData->getDataByKey($name));
    }

    /**
     * @return string
     */
    abstract protected function getBaseDefaultCarrierTitle();

    /**
     * @return string
     */
    abstract protected function getBaseDefaultMethodTitle();

    public function shouldOnlyApplyIfAvailable(DataObject $configData)
    {
        return $this->getFieldValue(self::KEY_ONLY_APPLY_IF_AVAILABLE, $configData);
    }

    public function getDefaultCarrierTitle(DataObject $configData)
    {
        return $this->getFieldValue(self::KEY_DEFAULT_CARRIER_TITLE, $configData);
    }

    public function shouldForceDefaultCarrierTitle(DataObject $configData)
    {
        return $this->getFieldValue(self::KEY_FORCE_DEFAULT_CARRIER_TITLE, $configData);
    }

    public function getDefaultMethodTitle(DataObject $configData)
    {
        return $this->getFieldValue(self::KEY_DEFAULT_METHOD_TITLE, $configData);
    }

    public function shouldForceDefaultMethodTitle(DataObject $configData)
    {
        return $this->getFieldValue(self::KEY_FORCE_DEFAULT_METHOD_TITLE, $configData);
    }
}
