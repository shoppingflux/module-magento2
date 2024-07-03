<?php

namespace ShoppingFeed\Manager\Model\SalesRule\Rule\Action\Discount\Marketplace;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\SalesRule\Model\DeltaPriceRound;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\Rule\Action\Discount\CartFixed as BaseCartFixed;
use Magento\SalesRule\Model\Rule\Action\Discount\Data as DiscountData;
use Magento\SalesRule\Model\Rule\Action\Discount\DataFactory as DiscountDataFactory;
use Magento\SalesRule\Model\Validator;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as SalesOrderImporterInterface;

class CartFixed extends BaseCartFixed
{
    const ACTION_CODE = 'sfm_marketplace_cart_fixed';

    /**
     * @var SalesOrderImporterInterface
     */
    private $salesOrderImporter;

    public function __construct(
        Validator $validator,
        DiscountDataFactory $discountDataFactory,
        PriceCurrencyInterface $priceCurrency,
        DeltaPriceRound $deltaPriceRound,
        SalesOrderImporterInterface $salesOrderImporter
    ) {
        $this->salesOrderImporter = $salesOrderImporter;
        parent::__construct($validator, $discountDataFactory, $priceCurrency, $deltaPriceRound);
    }

    /**
     * @param Rule $rule
     * @param AbstractItem $item
     * @param float $qty
     * @return DiscountData
     */
    public function calculate($rule, $item, $qty)
    {
        $order = $this->salesOrderImporter->getCurrentlyImportedMarketplaceOrder();

        if ((null !== $order) && ($amount = $order->getCartDiscountAmount())) {
            $rule = clone $rule;
            $rule->setDiscountAmount($amount);

            return parent::calculate($rule, $item, $qty);
        }

        return $this->discountFactory->create();
    }
}