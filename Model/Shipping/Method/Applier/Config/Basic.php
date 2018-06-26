<?php

namespace ShoppingFeed\Manager\Model\Shipping\Method\Applier\Config;

use Magento\Framework\DataObject;
use Magento\Shipping\Model\Config\Source\Allmethods as ShippingMethodSource;
use Magento\Ui\Component\Form\Element\DataType\Text as UiText;
use ShoppingFeed\Manager\Model\Config\FieldFactoryInterface;
use ShoppingFeed\Manager\Model\Config\Field\Select;
use ShoppingFeed\Manager\Model\Config\Value\HandlerFactoryInterface as ValueHandlerFactoryInterface;
use ShoppingFeed\Manager\Model\Config\Value\Handler\Option as OptionHandler;
use ShoppingFeed\Manager\Model\Shipping\Carrier\Marketplace as MarketplaceCarrier;
use ShoppingFeed\Manager\Model\Shipping\Method\Applier\AbstractConfig;


class Basic extends AbstractConfig implements BasicInterface
{
    const KEY_FULL_SHIPPING_METHOD_CODE = 'full_shipping_method_code';

    /**
     * @var ShippingMethodSource
     */
    private $shippingMethodSource;

    /**
     * @param FieldFactoryInterface $fieldFactory
     * @param ValueHandlerFactoryInterface $valueHandlerFactory
     * @param ShippingMethodSource $shippingMethodSource
     */
    public function __construct(
        FieldFactoryInterface $fieldFactory,
        ValueHandlerFactoryInterface $valueHandlerFactory,
        ShippingMethodSource $shippingMethodSource
    ) {
        $this->shippingMethodSource = $shippingMethodSource;
        parent::__construct($fieldFactory, $valueHandlerFactory);
    }

    public function getBaseFields()
    {
        return array_merge(
            parent::getBaseFields(),
            [
                $this->fieldFactory->create(
                    Select::TYPE_CODE,
                    [
                        'name' => self::KEY_FULL_SHIPPING_METHOD_CODE,
                        'valueHandler' => $this->valueHandlerFactory->create(
                            OptionHandler::TYPE_CODE,
                            [
                                'dataType' => UiText::NAME,
                                'optionArray' => $this->shippingMethodSource->toOptionArray(),
                            ]
                        ),
                        'isRequired' => true,
                        'label' => __('Shipping Method'),
                        'sortOrder' => 10,
                    ]
                ),
            ]
        );
    }

    /**
     * @return string
     */
    protected function getBaseDefaultCarrierTitle()
    {
        return __('Carrier Title');
    }

    /**
     * @return string
     */
    protected function getBaseDefaultMethodTitle()
    {
        return __('Method Title');
    }

    /**
     * @param DataObject $configData
     * @return string
     */
    private function getShippingMethodFullCode(DataObject $configData)
    {
        return trim($this->getFieldValue(self::KEY_FULL_SHIPPING_METHOD_CODE, $configData));
    }

    /**
     * @param DataObject $configData
     * @param int $index
     * @return string|null
     */
    private function getShippingMethodPart(DataObject $configData, $index)
    {
        $methodParts = array_filter(explode('_', $this->getShippingMethodFullCode($configData)));
        return (2 === count($methodParts)) && isset($methodParts[$index]) ? $methodParts[$index] : null;
    }

    public function getShippingCarrierCode(DataObject $configData)
    {
        return $this->getShippingMethodPart($configData, 0) ?? MarketplaceCarrier::CARRIER_CODE;
    }

    public function getShippingMethodCode(DataObject $configData)
    {
        return $this->getShippingMethodPart($configData, 1) ?? MarketplaceCarrier::METHOD_CODE;
    }
}
