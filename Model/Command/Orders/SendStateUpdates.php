<?php

namespace ShoppingFeed\Manager\Model\Command\Orders;

use Magento\Framework\DataObject;
use ShoppingFeed\Manager\Model\AbstractCommand;
use ShoppingFeed\Manager\Model\Command\ConfigInterface;
use ShoppingFeed\Manager\Model\Marketplace\Order\Manager as MarketplaceOrderManager;

class SendStateUpdates extends AbstractCommand
{
    /**
     * @var MarketplaceOrderManager
     */
    private $marketplaceOrderManager;

    /**
     * @param ConfigInterface $config
     * @param MarketplaceOrderManager $marketplaceOrderManager
     */
    public function __construct(ConfigInterface $config, MarketplaceOrderManager $marketplaceOrderManager)
    {
        $this->marketplaceOrderManager = $marketplaceOrderManager;
        parent::__construct($config);
    }

    public function getLabel()
    {
        return __('Send State Updates');
    }

    /**
     * @param DataObject $configData
     * @throws \Exception
     */
    public function run(DataObject $configData)
    {
        foreach ($this->getConfig()->getStores($configData) as $store) {
            $this->marketplaceOrderManager->notifyStoreOrderUpdates($store);
        }
    }
}
