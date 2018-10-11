<?php

namespace ShoppingFeed\Manager\Payment\Gateway\Validator;

use ShoppingFeed\Manager\Model\Sales\Order\ImporterInterface as OrderImporterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Quote\Model\Quote\Payment as QuotePayment;

class AvailabilityValidator extends AbstractValidator
{
    public function validate(array $validationSubject)
    {
        $isAvailable = false;

        if (isset($validationSubject['payment'])
            && ($validationSubject['payment'] instanceof PaymentDataObject)
            && ($quotePayment = $validationSubject['payment']->getPayment())
            && ($quotePayment instanceof QuotePayment)
            && $quotePayment->getQuote()->getDataByKey(OrderImporterInterface::QUOTE_KEY_IS_SHOPPING_FEED_ORDER)
        ) {
            $isAvailable = true;
        }

        return $this->createResult($isAvailable);
    }
}
