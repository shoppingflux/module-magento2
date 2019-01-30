<?php

namespace ShoppingFeed\Manager\Model\Shipping\Method\Rule\Condition\Marketplace;

use Magento\Config\Model\Config\Source\Locale\Currency as CurrencySource;
use Magento\Framework\Model\AbstractModel;
use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Rule\Model\Condition\Context;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;
use ShoppingFeed\Manager\Api\Data\Shipping\Method\RuleInterface;

/**
 * @method string getAttribute()
 * @method $this setAttribute(string $attribute)
 */
class Order extends AbstractCondition
{
    const ATTRIBUTE_KEY_MARKETPLACE_ORDER_NUMBER = 'marketplace_order_number';
    const ATTRIBUTE_KEY_MARKETPLACE_NAME = 'marketplace_name';
    const ATTRIBUTE_KEY_CURRENCY_CODE = 'currency_code';
    const ATTRIBUTE_KEY_PRODUCT_AMOUNT = 'product_amount';
    const ATTRIBUTE_KEY_SHIPPING_AMOUNT = 'shipping_amount';
    const ATTRIBUTE_KEY_TOTAL_AMOUNT = 'total_amount';
    const ATTRIBUTE_KEY_RAW_SHIPMENT_CARRIER = 'raw_shipment_carrier';
    const ATTRIBUTE_KEY_RAW_PAYMENT_METHOD = 'raw_payment_method';
    const ATTRIBUTE_KEY_SHIPMENT_CARRIER = 'shipment_carrier';
    const ATTRIBUTE_KEY_PAYMENT_METHOD = 'payment_method';

    /**
     * @var CurrencySource
     */
    private $currencySource;

    /**
     * @param Context $context
     * @param CurrencySource $currencySource
     * @param array $data
     */
    public function __construct(Context $context, CurrencySource $currencySource, array $data = [])
    {
        $this->currencySource = $currencySource;
        parent::__construct($context, $data);
    }

    public function loadAttributeOptions()
    {
        $this->setData(
            'attribute_option',
            [
                self::ATTRIBUTE_KEY_MARKETPLACE_ORDER_NUMBER => __('Marketplace Number'),
                self::ATTRIBUTE_KEY_MARKETPLACE_NAME => __('Marketplace Name'),
                self::ATTRIBUTE_KEY_CURRENCY_CODE => __('Currency'),
                self::ATTRIBUTE_KEY_PRODUCT_AMOUNT => __('Product Amount'),
                self::ATTRIBUTE_KEY_SHIPPING_AMOUNT => __('Shipping Amount'),
                self::ATTRIBUTE_KEY_TOTAL_AMOUNT => __('Total Amount'),
                self::ATTRIBUTE_KEY_RAW_SHIPMENT_CARRIER => __('Shipment Carrier'),
                self::ATTRIBUTE_KEY_RAW_PAYMENT_METHOD => __('Payment Method'),
            ]
        );

        return $this;
    }

    public function getAttributeElement()
    {
        $element = parent::getAttributeElement();
        $element->setShowAsText(true);
        return $element;
    }

    public function getInputType()
    {
        switch ($this->getAttribute()) {
            case self::ATTRIBUTE_KEY_PRODUCT_AMOUNT:
            case self::ATTRIBUTE_KEY_SHIPPING_AMOUNT:
            case self::ATTRIBUTE_KEY_TOTAL_AMOUNT:
                return 'numeric';

            case self::ATTRIBUTE_KEY_CURRENCY_CODE:
                return 'select';
        }

        return 'string';
    }

    public function getValueElementType()
    {
        switch ($this->getAttribute()) {
            case self::ATTRIBUTE_KEY_CURRENCY_CODE:
                return 'select';
        }

        return 'text';
    }

    public function getValueSelectOptions()
    {
        if (!$this->hasData('value_select_options')) {
            switch ($this->getAttribute()) {
                case self::ATTRIBUTE_KEY_CURRENCY_CODE:
                    $options = $this->currencySource->toOptionArray();
                    break;

                default:
                    $options = [];
            }

            $this->setData('value_select_options', $options);
        }

        return $this->getData('value_select_options');
    }

    public function validate(AbstractModel $model)
    {
        if (!$model instanceof MarketplaceOrderInterface) {
            $marketplaceOrder = $model->getDataByKey(RuleInterface::KEY_VALIDATED_MARKETPLACE_ORDER);
        } else {
            $marketplaceOrder = $model;
        }

        if (!$marketplaceOrder instanceof MarketplaceOrderInterface) {
            return false;
        }

        if ($this->getAttribute() === self::ATTRIBUTE_KEY_RAW_SHIPMENT_CARRIER) {
            $this->setAttribute(self::ATTRIBUTE_KEY_SHIPMENT_CARRIER);
        } elseif ($this->getAttribute() === self::ATTRIBUTE_KEY_RAW_PAYMENT_METHOD) {
            $this->setAttribute(self::ATTRIBUTE_KEY_RAW_PAYMENT_METHOD);
        }

        return parent::validate($marketplaceOrder);
    }
}
