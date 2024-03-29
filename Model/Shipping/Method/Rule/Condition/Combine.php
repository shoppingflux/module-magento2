<?php

namespace ShoppingFeed\Manager\Model\Shipping\Method\Rule\Condition;

use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Rule\Model\Condition\Context;
use Magento\SalesRule\Model\Rule\Condition\Address as BaseAddressCondition;
use Magento\SalesRule\Model\Rule\Condition\Combine as CombineBase;
use ShoppingFeed\Manager\Model\Shipping\Method\Rule\Condition\Marketplace\Order as MarketplaceOrderCondition;

class Combine extends CombineBase
{
    /**
     * @var MarketplaceOrderCondition
     */
    private $marketplaceOrderCondition;

    /**
     * @param Context $context
     * @param EventManagerInterface $eventManager
     * @param BaseAddressCondition $conditionAddress
     * @param MarketplaceOrderCondition $marketplaceOrderCondition
     * @param array $data
     */
    public function __construct(
        Context $context,
        EventManagerInterface $eventManager,
        BaseAddressCondition $conditionAddress,
        MarketplaceOrderCondition $marketplaceOrderCondition,
        array $data = []
    ) {
        $this->marketplaceOrderCondition = $marketplaceOrderCondition;
        parent::__construct($context, $eventManager, $conditionAddress, $data);
        $this->setType(self::class);
    }

    public function getNewChildSelectOptions()
    {
        $conditions = parent::getNewChildSelectOptions();

        foreach ($conditions as $key => $condition) {
            if ($condition['value'] === \Magento\SalesRule\Model\Rule\Condition\Combine::class) {
                $conditions[$key]['value'] = self::class;
            }
        }

        $marketplaceOrderAttributes = $this->marketplaceOrderCondition->loadAttributeOptions()->getAttributeOption();
        $marketplaceOrderAttributeOptions = [];

        foreach ($marketplaceOrderAttributes as $code => $label) {
            $marketplaceOrderAttributeOptions[] = [
                'value' => MarketplaceOrderCondition::class . '|' . $code,
                'label' => $label,
            ];
        }

        $conditions[] = [
            'label' => __('Marketplace Order'),
            'value' => $marketplaceOrderAttributeOptions,
        ];

        return $conditions;
    }

    public function validate(AbstractModel $model)
    {
        $address = $model;

        if (!$address instanceof QuoteAddress) {
            if ($model->getQuote()->isVirtual()) {
                $address = $model->getQuote()->getBillingAddress();
            } else {
                $address = $model->getQuote()->getShippingAddress();
            }
        }

        if ('payment_method' == $this->getAttribute() && !$address->hasPaymentMethod()) {
            $address->setPaymentMethod($model->getQuote()->getPayment()->getMethod());
        }

        return parent::validate($address);
    }
}
