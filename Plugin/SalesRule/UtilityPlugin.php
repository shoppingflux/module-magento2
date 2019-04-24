<?php

namespace ShoppingFeed\Manager\Plugin\SalesRule;

use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\Utility as SalesRuleUtility;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as OrderImporterInterface;

class UtilityPlugin
{
    /**
     * @param SalesRuleUtility $subject
     * @param callable $proceed
     * @param Rule $rule
     * @param QuoteAddress $address
     * @return bool
     */
    public function aroundCanProcessRule(SalesRuleUtility $subject, callable $proceed, $rule, $address)
    {
        $result = (bool) $proceed($rule, $address);

        if (($address instanceof QuoteAddress)
            && ($quote = $address->getQuote())
            && $quote->getDataByKey(OrderImporterInterface::QUOTE_KEY_IS_SHOPPING_FEED_ORDER)
        ) {
            return false;
        }

        return $result;
    }
}
