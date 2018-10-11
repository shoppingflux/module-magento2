<?php

namespace ShoppingFeed\Manager\Model\Command\Orders;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Model\AbstractCommand;
use ShoppingFeed\Manager\Model\Command\ConfigInterface;
use ShoppingFeed\Manager\Model\Marketplace\Order\Importer as MarketplaceOrderImporter;
use ShoppingFeed\Manager\Model\Marketplace\Order\Manager as MarketplaceOrderManager;

class FetchMarketplaceOrders extends AbstractCommand
{
    /**
     * @var MarketplaceOrderManager
     */
    private $marketplaceOrderManager;

    /**
     * @var MarketplaceOrderImporter
     */
    private $marketplaceOrderImporter;

    /**
     * @param ConfigInterface $config
     * @param MarketplaceOrderManager $marketplaceOrderManager
     * @param MarketplaceOrderImporter $marketplaceOrderImporter
     */
    public function __construct(
        ConfigInterface $config,
        MarketplaceOrderManager $marketplaceOrderManager,
        MarketplaceOrderImporter $marketplaceOrderImporter
    ) {
        $this->marketplaceOrderManager = $marketplaceOrderManager;
        $this->marketplaceOrderImporter = $marketplaceOrderImporter;
        parent::__construct($config);
    }

    public function getLabel()
    {
        return __('Fetch Marketplace Orders');
    }

    /**
     * @param DataObject $configData
     * @throws \Exception
     * @throws LocalizedException
     */
    public function run(DataObject $configData)
    {
        foreach ($this->getConfig()->getStores($configData) as $store) {
            $importableOrders = $this->marketplaceOrderManager->getStoreImportableApiOrders($store);
            $this->marketplaceOrderImporter->importStoreOrders($importableOrders, $store);
        }
    }
}
