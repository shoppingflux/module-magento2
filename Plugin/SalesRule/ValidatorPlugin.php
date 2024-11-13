<?php

namespace ShoppingFeed\Manager\Plugin\SalesRule;

use Magento\Quote\Model\Quote\Address;
use Magento\Rule\Model\ResourceModel\Rule\Collection\AbstractCollection as AbstractRuleCollection;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\Validator;
use ShoppingFeed\Manager\Model\SalesRule\Rule\Action\Discount\Marketplace\CartFixed as MarketplaceCartFixed;

class ValidatorPlugin
{
    /**
     * @var Rule[]
     */
    private $sfmCartFixedRules = [];

    /**
     * @param Rule $rule
     * @return bool
     */
    public function isSfmCartFixedRule(Rule $rule)
    {
        if ($rule->getSimpleAction() === MarketplaceCartFixed::ACTION_CODE) {
            return true;
        }

        foreach ($this->sfmCartFixedRules as $cartFixedRule) {
            if ((int) $rule->getId() === (int) $cartFixedRule->getId()) {
                return true;
            }
        }

        return false;
    }

    public function aroundInitTotals(Validator $subject, callable $proceed, $items, Address $address)
    {
        if (!$items) {
            return $proceed($items, $address);
        }

        try {
            $getRulesMethod = new \ReflectionMethod(Validator::class, '_getRules');
            $getRulesMethod->setAccessible(true);
            $rules = $getRulesMethod->invoke($subject, $address);
        } catch (\Exception $e) {
            $rules = $subject->getRules($address);
        }

        if ($rules instanceof AbstractRuleCollection) {
            /** @var Rule $rule */
            foreach ($rules->getItems() as $rule) {
                if ($rule->getSimpleAction() === MarketplaceCartFixed::ACTION_CODE) {
                    $this->sfmCartFixedRules[] = $rule;
                    // Make sure that item totals are initialized (necessary to reuse the "cart_fixed" behavior later).
                    $rule->setSimpleAction(Rule::CART_FIXED_ACTION);
                }
            }
        }

        $result = $proceed($items, $address);

        foreach ($this->sfmCartFixedRules as $rule) {
            $rule->setSimpleAction(MarketplaceCartFixed::ACTION_CODE);
        }

        $this->sfmCartFixedRules = [];

        return $result;
    }
}