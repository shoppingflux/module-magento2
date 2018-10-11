<?php

namespace ShoppingFeed\Manager\Model\Shipping\Method\Applier;

use Magento\Framework\DataObject;
use ShoppingFeed\Manager\Model\Config\Basic\AbstractConfig as BaseConfig;
use ShoppingFeed\Manager\Model\Config\FieldInterface;
use ShoppingFeed\Manager\Model\Config\Field\Checkbox;
use ShoppingFeed\Manager\Model\Config\Field\TextBox;
use ShoppingFeed\Manager\Model\Config\Value\Handler\Text as TextHandler;

abstract class AbstractConfig extends BaseConfig implements ConfigInterface
{
    const KEY_ONLY_APPLY_IF_AVAILABLE = 'only_apply_if_available';
    const KEY_DEFAULT_CARRIER_TITLE = 'default_carrier_title';
    const KEY_FORCE_DEFAULT_CARRIER_TITLE = 'force_default_carrier_title';
    const KEY_DEFAULT_METHOD_TITLE = 'default_method_title';
    const KEY_FORCE_DEFAULT_METHOD_TITLE = 'force_default_method_title';

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
