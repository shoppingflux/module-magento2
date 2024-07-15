<?php

namespace ShoppingFeed\Manager\Plugin\SalesRule;

use Magento\Framework\App\ObjectManager;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\Utility as SalesRuleUtility;
use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as OrderImporterInterface;

class UtilityPlugin
{
    /**
     * @var ValidatorPlugin
     */
    private $validatorPlugin;

    /**
     * @var OrderImporterInterface
     */
    private $salesOrderImporter;

    /**
     * @param ValidatorPlugin|null $validatorPlugin
     * @param OrderImporterInterface|null $salesOrderImporter
     */
    public function __construct(
        ValidatorPlugin $validatorPlugin = null,
        OrderImporterInterface $salesOrderImporter = null
    ) {
        $this->validatorPlugin = $validatorPlugin
            ?? ObjectManager::getInstance()->get(ValidatorPlugin::class);
        $this->salesOrderImporter =
            $salesOrderImporter ?? ObjectManager::getInstance()->get(OrderImporterInterface::class);
    }

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

        if ($result) {
            $isMarketplaceRule = $this->validatorPlugin->isSfmCartFixedRule($rule);

            $isMarketplaceQuote = (
                ($address instanceof QuoteAddress)
                && ($quote = $address->getQuote())
                && $quote->getDataByKey(OrderImporterInterface::QUOTE_KEY_IS_SHOPPING_FEED_ORDER)
            );

            $result = ($isMarketplaceRule === $isMarketplaceQuote);

            if ($result && $isMarketplaceQuote) {
                $result = (
                    ($order = $this->salesOrderImporter->getCurrentlyImportedMarketplaceOrder())
                    && ($order->getCartDiscountAmount() > 0)
                );
            }
        }

        return $result;
    }
}
