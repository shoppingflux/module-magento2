<?php

namespace ShoppingFeed\Manager\Model\Config\Source\Order\Status;

use Magento\Framework\App\Area as AppArea;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State as AppState;
use Magento\Sales\Model\Config\Source\Order\Status as StatusSource;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Config as OrderConfig;

class Complete extends StatusSource
{
    protected $_stateStatuses = Order::STATE_COMPLETE;

    /**
     * @var AppState
     */
    private $appState;

    /**
     * @param OrderConfig $orderConfig
     * @param AppState|null $appState
     */
    public function __construct(OrderConfig $orderConfig, ?AppState $appState = null)
    {
        parent::__construct($orderConfig);
        $this->appState = $appState ?? ObjectManager::getInstance()->get(AppState::class);
    }

    public function toOptionArray()
    {
        // The core status source uses the app state, but the area code is not set during database upgrades.
        // This causes the recurring upgrade of SFM account configurations to break.
        return $this->appState->emulateAreaCode(
            AppArea::AREA_ADMINHTML,
            function () {
                return parent::toOptionArray();
            }
        );
    }
}
