<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Stock;

use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Model\Stock\Item as StockItem;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryApi\Api\GetStockSourceLinksInterface;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Store\Model\Website as BaseWebsite;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;

class QtyResolver implements QtyResolverInterface
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
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

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
     * @var GetStockSourceLinksInterface|false|null
     */
    private $msiGetStockSourceLinksCommand = null;

    /**
     * @var GetSourceItemsBySkuInterface|false|null
     */
    private $msiGetSourceItemsBySkuCommand = null;

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
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        ModuleManager $moduleManager,
        ObjectManagerInterface $objectManager,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        StockRegistryInterface $stockRegistry
    ) {
        $this->moduleManager = $moduleManager;
        $this->objectManager = $objectManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * @return bool
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

    public function isUsingMsi()
    {
        return $this->isMsiRequiredModulesEnabled();
    }

    /**
     * @return StockResolverInterface|null
     */
    private function getMsiStockResolver()
    {
        if (null === $this->msiStockResolver) {
            if (
                $this->isMsiRequiredModulesEnabled()
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
            if (
                $this->isMsiRequiredModulesEnabled()
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
            if (
                $this->isMsiRequiredModulesEnabled()
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
     * @return GetStockSourceLinksInterface|null
     */
    private function getMsiGetStockSourceLinksCommand()
    {
        if (null === $this->msiGetStockSourceLinksCommand) {
            if (
                $this->isMsiRequiredModulesEnabled()
                && interface_exists('Magento\InventoryApi\Api\GetStockSourceLinksInterface')
            ) {
                try {
                    $this->msiGetStockSourceLinksCommand = $this->objectManager->create(
                        GetStockSourceLinksInterface::class
                    );
                } catch (\Exception $e) {
                    $this->msiGetStockSourceLinksCommand = false;
                }
            } else {
                $this->msiGetStockSourceLinksCommand = false;
            }
        }

        return is_object($this->msiGetStockSourceLinksCommand) ? $this->msiGetStockSourceLinksCommand : null;
    }

    /**
     * @return GetSourceItemsBySkuInterface|null
     */
    private function getMsiGetSourceItemsBySkuCommand()
    {
        if (null === $this->msiGetSourceItemsBySkuCommand) {
            if (
                $this->isMsiRequiredModulesEnabled()
                && interface_exists('Magento\InventoryApi\Api\GetSourceItemsBySkuInterface')
            ) {
                try {
                    $this->msiGetSourceItemsBySkuCommand = $this->objectManager->create(
                        GetSourceItemsBySkuInterface::class
                    );
                } catch (\Exception $e) {
                    $this->msiGetSourceItemsBySkuCommand = false;
                }
            } else {
                $this->msiGetSourceItemsBySkuCommand = false;
            }
        }

        return is_object($this->msiGetSourceItemsBySkuCommand) ? $this->msiGetSourceItemsBySkuCommand : null;
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
     * @param GetStockSourceLinksInterface $getStockSourceLinksCommand
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySkuCommand
     * @param int $stockId
     * @param string $sku
     * @return float
     */
    private function getMsiSkuStockQuantity(
        GetStockSourceLinksInterface $getStockSourceLinksCommand,
        GetSourceItemsBySkuInterface $getSourceItemsBySkuCommand,
        $stockId,
        $sku
    ) {
        $quantity = 0.0;

        $this->searchCriteriaBuilder->addFilter(StockSourceLinkInterface::STOCK_ID, $stockId);
        $sourceLinksSearchCriteria = $this->searchCriteriaBuilder->create();
        $sourceLinksResult = $getStockSourceLinksCommand->execute($sourceLinksSearchCriteria);
        $sourceItems = $getSourceItemsBySkuCommand->execute($sku);
        $sourceQuantities = [];

        foreach ($sourceItems as $sourceItem) {
            $sourceQuantities[$sourceItem->getSourceCode()] = $sourceItem->getQuantity();
        }

        foreach ($sourceLinksResult->getItems() as $sourceLink) {
            $sourceCode = $sourceLink->getSourceCode();

            if (isset($sourceQuantities[$sourceCode])) {
                $quantity += $sourceQuantities[$sourceCode];
            }
        }

        return $quantity;
    }

    /**
     * @param CatalogProduct $product
     * @param StoreInterface $store
     * @param string $msiQuantityType
     * @return float|false|null
     */
    private function getCatalogProductMsiQuantity(CatalogProduct $product, StoreInterface $store, $msiQuantityType)
    {
        $quantity = false;
        $stockResolver = $this->getMsiStockResolver();
        $getProductSalableQtyCommand = $this->getMsiGetProductSalableQtyCommand();
        $getStockItemConfigurationCommand = $this->getMsiGetStockItemConfigurationCommand();
        $getStockSourceLinksCommand = $this->getMsiGetStockSourceLinksCommand();
        $getSourceItemsBySkuCommand = $this->getMsiGetSourceItemsBySkuCommand();

        if (
            (null !== $stockResolver)
            && (null !== $getProductSalableQtyCommand)
            && (null !== $getStockItemConfigurationCommand)
            && (null !== $getStockSourceLinksCommand)
            && (null !== $getSourceItemsBySkuCommand)
        ) {
            try {
                $sku = $product->getSku();
                $stockId = $this->getMsiWebsiteStockId($stockResolver, $store->getBaseWebsite());
                $stockItemConfiguration = $getStockItemConfigurationCommand->execute($sku, $stockId);

                if ($stockItemConfiguration->isManageStock()) {
                    if (static::MSI_QUANTITY_TYPE_STOCK !== $msiQuantityType) {
                        $salableQuantity = $getProductSalableQtyCommand->execute($sku, $stockId);
                    }

                    if (static::MSI_QUANTITY_TYPE_SALABLE !== $msiQuantityType) {
                        $stockQuantity = $this->getMsiSkuStockQuantity(
                            $getStockSourceLinksCommand,
                            $getSourceItemsBySkuCommand,
                            $stockId,
                            $sku
                        );
                    }

                    switch ($msiQuantityType) {
                        case static::MSI_QUANTITY_TYPE_STOCK:
                            $quantity = $stockQuantity;
                            break;
                        case static::MSI_QUANTITY_TYPE_MAXIMUM:
                            $quantity = max($salableQuantity, $stockQuantity);
                            break;
                        case static::MSI_QUANTITY_TYPE_MINIMUM:
                            $quantity = min($salableQuantity, $stockQuantity);
                            break;
                        default:
                            $quantity = $salableQuantity;
                            break;
                    }
                } else {
                    $quantity = null;
                }
            } catch (SkuIsNotAssignedToStockException $e) {
                $quantity = 0.0;
            } catch (\Exception $e) {
                $quantity = false;
            }
        }

        return $quantity;
    }

    public function getCatalogProductQuantity(CatalogProduct $product, StoreInterface $store, $msiQuantityType)
    {
        $quantity = $this->getCatalogProductMsiQuantity($product, $store, $msiQuantityType);

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
