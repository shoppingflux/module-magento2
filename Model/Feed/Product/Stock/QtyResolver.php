<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Stock;

use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Model\Stock\Item as StockItem;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Store\Model\Website as BaseWebsite;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;

class QtyResolver
{
    const MSI_REQUIRED_MODULE_NAMES = [
        'Magento_InventoryConfiguration',
        'Magento_InventoryConfigurationApi',
        'Magento_InventorySales',
        'Magento_InventorySalesApi',
    ];

    /**
     * @var ModuleManager
     */
    private $moduleManager;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var StockRegistryInterface $stockRegistry
     */
    private $stockRegistry;

    /**
     * @var bool|null
     */
    private $isMsiRequiredModulesEnabled = null;

    /**
     * @var StockResolverInterface|false|null
     */
    private $msiStockResolver = null;

    /**
     * @var GetStockItemConfigurationInterface|false|null
     */
    private $msiGetStockItemConfigurationCommand = null;

    /**
     * @var GetProductSalableQtyInterface|false|null
     */
    private $msiGetProductSalableQtyCommand = null;

    /**
     * @var (int|false)[]
     */
    private $msiWebsiteStockIds = [];

    /**
     * The ObjectManager is used for backwards compatibility with Magento versions prior to 2.3.X, for which both
     * @see \Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface,
     * @see \Magento\InventorySalesApi\Api\GetProductSalableQtyInterface and
     * @see \Magento\InventorySalesApi\Api\StockResolverInterface do not exist.
     * @param ModuleManager $moduleManager
     * @param ObjectManagerInterface $objectManager
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        ModuleManager $moduleManager,
        ObjectManagerInterface $objectManager,
        StockRegistryInterface $stockRegistry
    ) {
        $this->moduleManager = $moduleManager;
        $this->objectManager = $objectManager;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * @return bool|null
     */
    private function isMsiRequiredModulesEnabled()
    {
        if (null === $this->isMsiRequiredModulesEnabled) {
            $this->isMsiRequiredModulesEnabled = true;

            foreach (static::MSI_REQUIRED_MODULE_NAMES as $moduleName) {
                if (!$this->moduleManager->isEnabled($moduleName)) {
                    $this->isMsiRequiredModulesEnabled = false;
                    break;
                }
            }
        }

        return $this->isMsiRequiredModulesEnabled;
    }

    /**
     * @return StockResolverInterface|null
     */
    private function getMsiStockResolver()
    {
        if (null === $this->msiStockResolver) {
            if ($this->isMsiRequiredModulesEnabled()
                && interface_exists('Magento\InventorySalesApi\Api\StockResolverInterface')
            ) {
                try {
                    $this->msiStockResolver = $this->objectManager->create(StockResolverInterface::class);
                } catch (\Exception $e) {
                    $this->msiStockResolver = false;
                }
            } else {
                $this->msiStockResolver = false;
            }
        }

        return is_object($this->msiStockResolver) ? $this->msiStockResolver : null;
    }

    /**
     * @return GetStockItemConfigurationInterface|null
     */
    private function getMsiGetStockItemConfigurationCommand()
    {
        if (null === $this->msiGetStockItemConfigurationCommand) {
            if ($this->isMsiRequiredModulesEnabled()
                && interface_exists('Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface')
            ) {
                try {
                    $this->msiGetStockItemConfigurationCommand = $this->objectManager->create(
                        GetStockItemConfigurationInterface::class
                    );
                } catch (\Exception $e) {
                    $this->msiGetStockItemConfigurationCommand = false;
                }
            } else {
                $this->msiGetStockItemConfigurationCommand = false;
            }
        }

        return !is_object($this->msiGetStockItemConfigurationCommand)
            ? null
            : $this->msiGetStockItemConfigurationCommand;
    }

    /**
     * @return GetProductSalableQtyInterface|null
     */
    private function getMsiGetProductSalableQtyCommand()
    {
        if (null === $this->msiGetProductSalableQtyCommand) {
            if ($this->isMsiRequiredModulesEnabled()
                && interface_exists('Magento\InventorySalesApi\Api\GetProductSalableQtyInterface')
            ) {
                try {
                    $this->msiGetProductSalableQtyCommand = $this->objectManager->create(
                        GetProductSalableQtyInterface::class
                    );
                } catch (\Exception $e) {
                    $this->msiGetProductSalableQtyCommand = false;
                }
            } else {
                $this->msiGetProductSalableQtyCommand = false;
            }
        }

        return is_object($this->msiGetProductSalableQtyCommand) ? $this->msiGetProductSalableQtyCommand : null;
    }

    /**
     * @param StockResolverInterface $stockResolver
     * @param BaseWebsite $website
     * @return int
     * @throws NoSuchEntityException
     */
    private function getMsiWebsiteStockId(StockResolverInterface $stockResolver, BaseWebsite $website)
    {
        $websiteId = (int) $website->getId();

        if (!isset($this->msiWebsiteStockIds[$websiteId])) {
            try {
                $stock = $stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $website->getCode());
                $this->msiWebsiteStockIds[$websiteId] = (int) $stock->getId();
            } catch (NoSuchEntityException $e) {
                $this->msiWebsiteStockIds[$websiteId] = false;
            }
        }

        if (false === $this->msiWebsiteStockIds[$websiteId]) {
            throw new NoSuchEntityException(__('No linked stock found'));
        }

        return $this->msiWebsiteStockIds[$websiteId];
    }

    /**
     * @param CatalogProduct $product
     * @param StoreInterface $store
     * @return float|null
     */
    public function getCatalogProductQuantity(CatalogProduct $product, StoreInterface $store)
    {
        $quantity = false;
        $stockResolver = $this->getMsiStockResolver();
        $getProductSalableQtyCommand = $this->getMsiGetProductSalableQtyCommand();
        $getStockItemConfigurationCommand = $this->getMsiGetStockItemConfigurationCommand();

        if ((null !== $stockResolver)
            && (null !== $getProductSalableQtyCommand)
            && (null !== $getStockItemConfigurationCommand)
        ) {
            try {
                $sku = $product->getSku();
                $stockId = $this->getMsiWebsiteStockId($stockResolver, $store->getBaseWebsite());
                $stockItemConfiguration = $getStockItemConfigurationCommand->execute($sku, $stockId);

                if ($stockItemConfiguration->isManageStock()) {
                    $quantity = $getProductSalableQtyCommand->execute($sku, $stockId);
                } else {
                    $quantity = null;
                }
            } catch (SkuIsNotAssignedToStockException $e) {
                $quantity = 0.0;
            } catch (\Exception $e) {
                $quantity = false;
            }
        }

        if (false === $quantity) {
            $stockItem = $this->stockRegistry->getStockItem($product->getId(), $store->getBaseWebsiteId());

            if ($stockItem instanceof StockItem) {
                // Ensure that the right system configuration values will be used.
                $stockItem->setStoreId($store->getBaseStoreId());
            }

            if ($stockItem->getManageStock()) {
                $quantity = $stockItem->getQty();
            } else {
                $quantity = null;
            }
        }

        return $quantity;
    }
}
