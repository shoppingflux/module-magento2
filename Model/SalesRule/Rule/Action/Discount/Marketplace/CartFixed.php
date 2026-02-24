<?php

namespace ShoppingFeed\Manager\Model\SalesRule\Rule\Action\Discount\Marketplace;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\SalesRule\Model\DeltaPriceRound;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\Rule\Action\Discount\CartFixed as BaseCartFixed;
use Magento\SalesRule\Model\Rule\Action\Discount\Data as DiscountData;
use Magento\SalesRule\Model\Rule\Action\Discount\DataFactory as DiscountDataFactory;
use Magento\SalesRule\Model\Rule\Action\Discount\ExistingDiscountRuleCollector;
use Magento\SalesRule\Model\Validator;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as SalesOrderImporterInterface;
use ShoppingFeed\Manager\Model\Sales\Order\ImportStateInterface as SalesOrderImportStateInterface;

class CartFixed extends BaseCartFixed
{
    const ACTION_CODE = 'sfm_marketplace_cart_fixed';

    /**
     * @var SalesOrderImporterInterface
     */
    private $salesOrderImporter;

    /**
     * @var SalesOrderImportStateInterface
     */
    private $salesOrderImportState;

    public function __construct(
        Validator $validator,
        DiscountDataFactory $discountDataFactory,
        PriceCurrencyInterface $priceCurrency,
        DeltaPriceRound $deltaPriceRound,
        SalesOrderImporterInterface $salesOrderImporter,
        ?SalesOrderImportStateInterface $salesOrderImportState = null
    ) {
        $this->salesOrderImporter = $salesOrderImporter;
        $this->salesOrderImportState = $salesOrderImportState
            ?? ObjectManager::getInstance()->get(SalesOrderImportStateInterface::class);

        /**
         * Avoid the parent constructor call integrity check.
         * Because the required parameters change depending on the Magento version,
         * it is not possible to ensure that only the call relevant to the current version is checked.
         */
        $this->callParentConstructor($validator, $discountDataFactory, $priceCurrency, $deltaPriceRound);
    }

    /**
     * @param Rule $rule
     * @param AbstractItem $item
     * @param float $qty
     * @return DiscountData
     */
    public function calculate($rule, $item, $qty)
    {
        $store = $this->salesOrderImportState->getImportRunningForStore();
        $order = $this->salesOrderImportState->getCurrentlyImportedMarketplaceOrder();

        if ((null !== $order) && ($amount = $order->getCartDiscountAmount())) {
            $rule = clone $rule;

            $rule->setDiscountAmount(
                $this->priceCurrency
                    ->getCurrency(null, $order->getCurrencyCode())
                    ->convert($amount, $store->getBaseStore()->getBaseCurrency())
            );

            return parent::calculate($rule, $item, $qty);
        }

        return $this->discountFactory->create();
    }

    /**
     * @param Validator $validator
     * @param DiscountDataFactory $discountDataFactory
     * @param PriceCurrencyInterface $priceCurrency
     * @param DeltaPriceRound $deltaPriceRound
     * @return void
     */
    private function callParentConstructor(
        Validator $validator,
        DiscountDataFactory $discountDataFactory,
        PriceCurrencyInterface $priceCurrency,
        DeltaPriceRound $deltaPriceRound
    ) {
        if (static::isMagentoVersionAtLeast248()) {
            parent::__construct(
                $validator,
                $discountDataFactory,
                $priceCurrency,
                $deltaPriceRound,
                ObjectManager::getInstance()->get(ExistingDiscountRuleCollector::class)
            );
        } else {
            parent::__construct(
                $validator,
                $discountDataFactory,
                $priceCurrency,
                $deltaPriceRound
            );
        }
    }

    /**
     * @return bool
     */
    private static function isMagentoVersionAtLeast248(): bool
    {
        static $hasCollectorParam = null;

        if (null !== $hasCollectorParam) {
            return $hasCollectorParam;
        }

        try {
            $hasCollectorParam = false;

            $class = new \ReflectionClass(BaseCartFixed::class);
            $params = $class->getConstructor()?->getParameters() ?? [];

            foreach ($params as $param) {
                if ($param->getName() === 'existingDiscountRuleCollector') {
                    $hasCollectorParam = true;
                    break;
                }
            }
        } catch (\ReflectionException $e) {
            $hasCollectorParam = false;
        }

        return $hasCollectorParam;
    }
}
