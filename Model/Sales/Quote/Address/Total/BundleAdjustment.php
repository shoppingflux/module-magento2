<?php

namespace ShoppingFeed\Manager\Model\Sales\Quote\Address\Total;

use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector;

class BundleAdjustment extends AbstractTotal
{
    const TAXABLE_TYPE = 'sfm_bundle_adjustment';
    const TAXABLE_CODE_PATTERN = 'sfm_bundle_adjustment_%d';

    public function collect(Quote $quote, ShippingAssignmentInterface $shippingAssignment, Total $total)
    {
        parent::collect($quote, $shippingAssignment, $total);

        /** @var Quote\Address $address */
        $address = $shippingAssignment->getShipping()->getAddress();

        if (
            ($extensionAttributes = $address->getExtensionAttributes())
            && is_array($adjustments = $extensionAttributes->getSfmBundleAdjustments())
            && !empty($adjustments)
        ) {
            $taxables = $address->getData('associated_taxables');

            if (!is_array($taxables)) {
                $taxables = [];
            } else {
                foreach ($taxables as $key => $taxable) {
                    if ($taxable[CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_TYPE] === static::TAXABLE_TYPE) {
                        unset($taxables[$key]);
                    }
                }
            }

            $store = $quote->getStore();

            foreach ($adjustments as $taxClassId => $adjustment) {
                $taxableCode = sprintf(static::TAXABLE_CODE_PATTERN, $taxClassId);
                $baseAdjustment = $adjustment;

                if ($store->getCurrentCurrencyCode() !== $store->getBaseCurrencyCode()) {
                    $baseAdjustment /= $store->getCurrentCurrencyRate();
                }

                $taxables[] = [
                    CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_TYPE => static::TAXABLE_TYPE,
                    CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_CODE => $taxableCode,
                    CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_UNIT_PRICE => $adjustment,
                    CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_BASE_UNIT_PRICE => $baseAdjustment,
                    CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_QUANTITY => 1,
                    CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_TAX_CLASS_ID => $taxClassId,
                    CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_PRICE_INCLUDES_TAX => true,
                    CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_ASSOCIATION_ITEM_CODE => CommonTaxCollector::ASSOCIATION_ITEM_CODE_FOR_QUOTE,
                ];
            }

            $address->setData('associated_taxables', $taxables);
        }

        return $this;
    }
}
