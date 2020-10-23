<?php

namespace ShoppingFeed\Manager\Plugin\Sales\Order;

use Magento\Framework\DataObjectFactory;
use Magento\Sales\Block\Order\Totals as TotalsBlock;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;

class TotalsBlockPlugin
{
    const BLOCK_FLAG_REGISTERED_TOTALS = 'sfm_registered_totals';
    const TOTAL_CODE_BUNDLE_ADJUSTMENT = 'sfm_bundle_adjustment';
    const TOTAL_CODE_MARKETPLACE_FEES = 'sfm_marketplace_fees';

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @param DataObjectFactory $dataObjectFactory
     */
    public function __construct(DataObjectFactory $dataObjectFactory)
    {
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * @param TotalsBlock $block
     * @param string $code
     * @param string $field
     * @param float $value
     * @param string $label
     * @param string|null $area
     */
    private function addMarketplaceTotalOnBlock(TotalsBlock $block, $code, $field, $value, $label, $area, $before)
    {
        if ($block->getTotal($code)) {
            return;
        }

        $total = $this->dataObjectFactory->create();

        $total->setData(
            [
                'code' => $code,
                'field' => $field,
                'value' => $value,
                'label' => $label,
                'area' => $area,
            ]
        );

        $block->addTotalBefore($total, $before);
    }

    /**
     * @param TotalsBlock $subject
     */
    private function registerMarketplaceTotalsOnBlock(TotalsBlock $subject)
    {
        if (!$subject->hasData(self::BLOCK_FLAG_REGISTERED_TOTALS)) {
            $subject->setData(self::BLOCK_FLAG_REGISTERED_TOTALS, true);

            $bundleAdjustment = (float) $subject->getSource()
                ->getData(MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_BUNDLE_ADJUSTMENT);

            if (!empty($bundleAdjustment)) {
                $this->addMarketplaceTotalOnBlock(
                    $subject,
                    self::TOTAL_CODE_BUNDLE_ADJUSTMENT,
                    MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_BUNDLE_ADJUSTMENT,
                    $bundleAdjustment,
                    __('Bundle Adjustment'),
                    null,
                    'shipping'
                );
            }

            $feesAmount = $subject->getSource()
                ->getData(MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_MARKETPLACE_FEES_AMOUNT);

            if ($feesAmount > 0) {
                $this->addMarketplaceTotalOnBlock(
                    $subject,
                    self::TOTAL_CODE_MARKETPLACE_FEES,
                    MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_MARKETPLACE_FEES_AMOUNT,
                    $feesAmount,
                    __('Marketplace Fees'),
                    'footer',
                    'grand_total'
                );
            }
        }
    }

    /**
     * @param TotalsBlock $subject
     * @param string $code
     */
    public function beforeGetTotal(TotalsBlock $subject, $code)
    {
        if (self::TOTAL_CODE_MARKETPLACE_FEES === $code) {
            $this->registerMarketplaceTotalsOnBlock($subject);
        }
    }

    /**
     * @param TotalsBlock $subject
     * @param array|null $area
     */
    public function beforeGetTotals(TotalsBlock $subject, $area = null)
    {
        $this->registerMarketplaceTotalsOnBlock($subject);
    }
}
