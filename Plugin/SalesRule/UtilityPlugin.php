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
     * @param bool $result
     * @param Rule $rule
     * @param QuoteAddress $address
     * @return bool
     */
    public function afterCanProcessRule(SalesRuleUtility $subject, $result, $rule, $address)
    {
        if (($address instanceof QuoteAddress)
            && ($quote = $address->getQuote())
            && $quote->getDataByKey(OrderImporterInterface::QUOTE_KEY_IS_SHOPPING_FEED_ORDER)
        ) {
            return false;
        }

        return $result;
    }
}
