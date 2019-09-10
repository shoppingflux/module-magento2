<?php

namespace ShoppingFeed\Manager\Plugin\Sales\Order;

use Magento\Framework\DataObjectFactory;
use Magento\Sales\Block\Order\Totals as TotalsBlock;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface as MarketplaceOrderInterface;

class TotalsBlockPlugin
{
    const BLOCK_FLAG_REGISTERED_TOTALS = 'sfm_registered_totals';
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
     * @param TotalsBlock $subject
     */
    private function registerMarketplaceTotalsOnBlock(TotalsBlock $subject)
    {
        if (!$subject->hasData(self::BLOCK_FLAG_REGISTERED_TOTALS)) {
            $subject->setData(self::BLOCK_FLAG_REGISTERED_TOTALS, true);

            if (!$subject->getTotal(self::TOTAL_CODE_MARKETPLACE_FEES)) {
                $feesAmount = $subject->getSource()
                    ->getData(MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_MARKETPLACE_FEES_AMOUNT);

                if ($feesAmount > 0) {
                    $total = $this->dataObjectFactory->create();

                    $total->setData(
                        [
                            'code' => self::TOTAL_CODE_MARKETPLACE_FEES,
                            'field' => MarketplaceOrderInterface::SALES_ENTITY_FIELD_NAME_MARKETPLACE_FEES_AMOUNT,
                            'value' => $feesAmount,
                            'label' => __('Marketplace Fees'),
                            'area' => 'footer',
                        ]
                    );

                    $subject->addTotalBefore($total, 'grand_total');
                }
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
