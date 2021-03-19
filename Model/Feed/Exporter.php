<?php

namespace ShoppingFeed\Manager\Model\Feed;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ProductMetadataInterface as AppMetadataInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem as FileSystem;
use Magento\Framework\Filesystem\Directory\ReadInterface as DirectoryReadInterface;
use Magento\Framework\Filesystem\Directory\WriteInterface as DirectoryWriteInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\UrlInterface;
use ShoppingFeed\Feed\Product\AbstractProduct as AbstractExportedProduct;
use ShoppingFeed\Feed\Product\Product as ExportedProduct;
use ShoppingFeed\Feed\ProductGenerator as FeedGenerator;
use ShoppingFeed\Feed\ProductGeneratorFactory as FeedGeneratorFactory;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\ConfigInterface as FeedConfigInterface;
use ShoppingFeed\Manager\Model\Feed\Exporter\ProductListWriter;
use ShoppingFeed\Manager\Model\Feed\Product as FeedProduct;
use ShoppingFeed\Manager\Model\Feed\Product\Export\State\ConfigInterface as ExportStateConfigInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\TypePoolInterface as SectionTypePoolInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Feed\Exporter as ExporterResource;

class Exporter
{
    const CHILDREN_EXPORT_MODE_NONE = 'none';
    const CHILDREN_EXPORT_MODE_SEPARATELY = 'separately';
    const CHILDREN_EXPORT_MODE_WITHIN_PARENTS = 'within_parents';
    const CHILDREN_EXPORT_MODE_BOTH = 'both';

    const PRODUCT_ID_ATTRIBUTE_NAME = 'id';

    /**
     * @var ExporterResource
     */
    private $resource;

    /**
     * @var AppMetadataInterface
     */
    private $appMetadata;

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @var FileSystem
     */
    private $fileSystem;

    /**
     * @var DirectoryReadInterface|null
     */
    private $mediaDirectoryReader = null;

    /**
     * @var DirectoryWriteInterface|null
     */
    private $mediaDirectoryWriter = null;

    /**
     * @var FeedGeneratorFactory
     */
    private $feedGeneratorFactory;

    /**
     * @var FeedConfigInterface
     */
    private $generalConfig;

    /**
     * @var ExportStateConfigInterface
     */
    private $exportStateConfig;

    /**
     * @var SectionTypePoolInterface
     */
    private $sectionTypePool;

    /**
     * @var string
     */
    private $feedDirectory;

    /**
     * @param FileSystem $fileSystem
     * @param ExporterResource $resource
     * @param AppMetadataInterface $appMetadata
     * @param ModuleListInterface $moduleList
     * @param FeedGeneratorFactory $feedGeneratorFactory
     * @param ConfigInterface $generalConfig
     * @param ExportStateConfigInterface $exportStateConfig
     * @param SectionTypePoolInterface $sectionTypePool
     * @param string $feedDirectory
     */
    public function __construct(
        FileSystem $fileSystem,
        ExporterResource $resource,
        AppMetadataInterface $appMetadata,
        ModuleListInterface $moduleList,
        FeedGeneratorFactory $feedGeneratorFactory,
        FeedConfigInterface $generalConfig,
        ExportStateConfigInterface $exportStateConfig,
        SectionTypePoolInterface $sectionTypePool,
        $feedDirectory
    ) {
        $this->fileSystem = $fileSystem;
        $this->resource = $resource;
        $this->appMetadata = $appMetadata;
        $this->moduleList = $moduleList;
        $this->feedGeneratorFactory = $feedGeneratorFactory;
        $this->generalConfig = $generalConfig;
        $this->exportStateConfig = $exportStateConfig;
        $this->sectionTypePool = $sectionTypePool;
        $this->feedDirectory = $feedDirectory;
    }

    /**
     * @return DirectoryReadInterface
     */
    private function getMediaDirectoryReader()
    {
        if (null === $this->mediaDirectoryReader) {
            $this->mediaDirectoryReader = $this->fileSystem->getDirectoryRead(DirectoryList::MEDIA);
        }

        return $this->mediaDirectoryReader;
    }

    /**
     * @return DirectoryWriteInterface|null
     * @throws FileSystemException
     */
    private function getMediaDirectoryWriter()
    {
        if (null === $this->mediaDirectoryWriter) {
            $this->mediaDirectoryWriter = $this->fileSystem->getDirectoryWrite(DirectoryList::MEDIA);
        }

        return $this->mediaDirectoryWriter;
    }

    /**
     * @param StoreInterface $store
     * @param ExportableProduct $product
     * @param bool $adaptAsRetained
     */
    private function adaptExportableProductSectionsData(
        StoreInterface $store,
        ExportableProduct $product,
        $adaptAsRetained
    ) {
        $adaptAsRetained = $adaptAsRetained || $product->isNonExportable();

        foreach ($this->sectionTypePool->getTypes() as $sectionType) {
            $typeAdapter = $sectionType->getAdapter();
            $typeId = $sectionType->getId();
            $productData = (array) $product->getSectionTypeData($typeId);

            if ($product->isParent()) {
                $productData = $typeAdapter->adaptParentProductData(
                    $store,
                    $productData,
                    $product->getChildrenSectionTypeData($typeId)
                );
            } elseif ($product->isBundle()) {
                $productData = $typeAdapter->adaptBundleProductData(
                    $store,
                    $productData,
                    $product->getChildrenSectionTypeData($typeId),
                    $product->getChildrenBundledQuantities()
                );
            } elseif ($product->isChild()) {
                $productData = $typeAdapter->adaptChildProductData($store, $productData);
            }

            if ($adaptAsRetained) {
                $productData = $typeAdapter->adaptRetainedProductData($store, $productData);
                $productData = $typeAdapter->adaptNonExportableProductData($store, $productData);
            }

            $product->setSectionTypeData($typeId, $productData);
        }
    }

    /**
     * @param StoreInterface $store
     * @param FeedGenerator $feedGenerator
     * @param \Iterator $productsIterator
     * @throws \Exception
     */
    private function writeStoreProductsToGenerator(
        StoreInterface $store,
        FeedGenerator $feedGenerator,
        \Iterator $productsIterator
    ) {
        $feedGenerator->addProcessor(
            function (ExportableProduct $product) use ($store) {
                $isRetained = $product->isRetained();
                $hasChildren = $product->hasChildren();

                if ($hasChildren) {
                    foreach ($product->getChildren() as $childProduct) {
                        $this->adaptExportableProductSectionsData($store, $childProduct, $isRetained);
                    }
                }

                if ($hasChildren || $isRetained) {
                    $this->adaptExportableProductSectionsData($store, $product, $isRetained);
                }

                return $product;
            }
        );

        $feedGenerator->addMapper(
            function (ExportableProduct $product, ExportedProduct $exportedProduct) use ($store) {
                $exportedProduct->setAttribute(self::PRODUCT_ID_ATTRIBUTE_NAME, $product->getId());

                foreach ($this->sectionTypePool->getTypes() as $sectionType) {
                    $adapter = $sectionType->getAdapter();

                    $adapter->exportBaseProductData(
                        $store,
                        $product->getSectionTypeData($sectionType->getId()),
                        $exportedProduct
                    );

                    $adapter->exportMainProductData(
                        $store,
                        $product->getSectionTypeData($sectionType->getId()),
                        $exportedProduct
                    );
                }

                if ($product->isParent()) {
                    $configurableAttributeCodes = $product->getConfigurableAttributeCodes();

                    foreach ($product->getChildren() as $childProduct) {
                        $exportedVariation = $exportedProduct->createVariation();
                        $exportedVariation->setAttribute(self::PRODUCT_ID_ATTRIBUTE_NAME, $childProduct->getId());

                        foreach ($this->sectionTypePool->getTypes() as $sectionType) {
                            $adapter = $sectionType->getAdapter();

                            $adapter->exportBaseProductData(
                                $store,
                                $childProduct->getSectionTypeData($sectionType->getId()),
                                $exportedVariation
                            );

                            $adapter->exportVariationProductData(
                                $store,
                                $childProduct->getSectionTypeData($sectionType->getId()),
                                $configurableAttributeCodes,
                                $exportedVariation
                            );
                        }
                    }
                }
            }
        );

        $feedGenerator->write($productsIterator);
    }

    /**
     * @param StoreInterface $store
     * @param int[]|null $productIds
     * @return \Iterator
     */
    private function getStoreProductsIterator(StoreInterface $store, $productIds = null)
    {
        $mainExportStates = [ FeedProduct::STATE_EXPORTED ];
        $retentionDuration = 0;
        $variationExportMode = $this->exportStateConfig->getChildrenExportMode($store);

        if ($this->exportStateConfig->shouldRetainPreviouslyExported($store)) {
            $mainExportStates[] = FeedProduct::STATE_RETAINED;
            $retentionDuration = $this->exportStateConfig->getPreviouslyExportedRetentionDuration($store);
        }

        $productsIterator = new \AppendIterator();

        $productsIterator->append(
            $this->resource->getExportableProductsIterator(
                $store->getId(),
                $this->sectionTypePool->getTypeIds(),
                $mainExportStates,
                $retentionDuration,
                in_array(
                    $variationExportMode,
                    [ self::CHILDREN_EXPORT_MODE_NONE, self::CHILDREN_EXPORT_MODE_SEPARATELY ],
                    true
                ),
                in_array(
                    $variationExportMode,
                    [ self::CHILDREN_EXPORT_MODE_BOTH, self::CHILDREN_EXPORT_MODE_SEPARATELY ],
                    true
                ),
                $productIds
            )
        );

        if (
            (self::CHILDREN_EXPORT_MODE_BOTH === $variationExportMode)
            || (self::CHILDREN_EXPORT_MODE_WITHIN_PARENTS === $variationExportMode)
        ) {
            $productsIterator->append(
                $this->resource->getExportableParentProductsIterator(
                    $store->getId(),
                    $this->sectionTypePool->getTypeIds(),
                    $mainExportStates,
                    [ FeedProduct::STATE_EXPORTED ],
                    $retentionDuration,
                    $productIds,
                    true
                )
            );
        }

        $productsIterator->append(
            $this->resource->getExportableBundleProductsIterator(
                $store->getId(),
                $this->sectionTypePool->getTypeIds(),
                $mainExportStates,
                $retentionDuration,
                $productIds
            )
        );

        return $productsIterator;
    }

    /**
     * @param AbstractExportedProduct $product
     * @return int|null
     */
    private function getExportedProductId(AbstractExportedProduct $product)
    {
        $attributes = $product->getAttributes();

        return !isset($attributes[self::PRODUCT_ID_ATTRIBUTE_NAME])
            ? null
            : (int) $attributes[self::PRODUCT_ID_ATTRIBUTE_NAME]->getValue();
    }

    /**
     * @param StoreInterface $store
     * @param int[] $productIds
     * @return AbstractExportedProduct[]
     * @throws \Exception
     */
    public function exportStoreProducts(StoreInterface $store, array $productIds)
    {
        FeedGenerator::registerWriter(ProductListWriter::ALIAS, ProductListWriter::class);

        $feedGenerator = $this->feedGeneratorFactory->create();
        $uri = uniqid('', true);
        $feedGenerator->setUri($uri);
        $feedGenerator->setWriter(ProductListWriter::ALIAS);

        // Filter out unrelated products, in case we end up with more products than expected.
        $feedGenerator->addFilter(
            function (ExportableProduct $product) use ($productIds) {
                return in_array($product->getId(), $productIds, true)
                    || !empty(array_intersect($product->getChildrenIds(), $productIds));
            }
        );

        $this->writeStoreProductsToGenerator(
            $store,
            $feedGenerator,
            $this->getStoreProductsIterator($store, $productIds)
        );

        $products = ProductListWriter::getUriProducts($uri);
        $exportableProducts = [];

        foreach ($products as $product) {
            $exportableProducts[] = $product;

            foreach ($product->getVariations() as $childProduct) {
                $childProductId = $this->getExportedProductId($childProduct);

                // Variations did not pass through the filter.
                if (in_array($childProductId, $productIds, true)) {
                    $exportableProducts[] = $childProduct;
                }
            }
        }

        return $exportableProducts;
    }

    /**
     * @param StoreInterface $store
     * @throws \Exception
     */
    public function exportStoreFeed(StoreInterface $store)
    {
        $mediaDirectoryReader = $this->getMediaDirectoryReader();
        $feedAbsoluteMediaPath = $mediaDirectoryReader->getAbsolutePath($this->feedDirectory) . '/';
        $feedRelativeMediaPath = $mediaDirectoryReader->getRelativePath($this->feedDirectory) . '/';

        $feedFileName = $store->getFeedFileNameBase() . '.xml';
        $feedAbsoluteFilePath = $feedAbsoluteMediaPath . '/' . $feedFileName;
        $feedAbsoluteTempFilePath = $feedAbsoluteFilePath . '.tmp';
        $feedRelativeFilePath = $feedRelativeMediaPath . '/' . $feedFileName;
        $feedRelativeTempFilePath = $feedRelativeFilePath . '.tmp';

        if (!$mediaDirectoryReader->isExist($feedRelativeMediaPath)) {
            $mediaDirectoryWriter = $this->getMediaDirectoryWriter();
            $mediaDirectoryWriter->create($feedRelativeMediaPath);
        }

        $feedGenerator = $this->feedGeneratorFactory->create();

        $feedGenerator->setPlatform(
            $this->appMetadata->getName() . ' ' . $this->appMetadata->getEdition(),
            $this->appMetadata->getVersion()
        );

        $baseStore = $store->getBaseStore();
        $feedGenerator->setAttribute('storeName', $baseStore->getName());
        $feedGenerator->setAttribute('storeUrl', $baseStore->getUrl());

        if ($this->moduleList->has('ShoppingFeed_Manager')) {
            $feedGenerator->setAttribute(
                'moduleVersion',
                $this->moduleList->getOne('ShoppingFeed_Manager')['setup_version'] ?? '?'
            );
        }

        if ($this->generalConfig->shouldUseGzipCompression($store)) {
            $feedAbsoluteFilePath .= '.gz';
            $feedAbsoluteTempFilePath .= '.gz';
            $feedRelativeFilePath .= '.gz';
            $feedRelativeTempFilePath .= '.gz';
            $feedGenerator->setUri('compress.zlib://' . $feedAbsoluteTempFilePath);
        } else {
            $feedGenerator->setUri('file://' . $feedAbsoluteTempFilePath);
        }

        $this->writeStoreProductsToGenerator($store, $feedGenerator, $this->getStoreProductsIterator($store));

        if (false === $this->getMediaDirectoryWriter()->renameFile($feedRelativeTempFilePath, $feedRelativeFilePath)) {
            throw new LocalizedException(
                __('Could not copy file "%1" to file "%2".', $feedAbsoluteTempFilePath, $feedAbsoluteFilePath)
            );
        }
    }

    /**
     * @param StoreInterface $store
     * @return string
     */
    public function getStoreFeedUrl(StoreInterface $store)
    {
        return $store->getBaseStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA)
            . $this->feedDirectory
            . '/'
            . $store->getFeedFileNameBase()
            . '.xml'
            . ($this->generalConfig->shouldUseGzipCompression($store) ? '.gz' : '');
    }

    /**
     * @param StoreInterface $store
     * @return bool
     */
    public function isStoreFeedAvailable(StoreInterface $store)
    {
        $mediaDirectoryReader = $this->getMediaDirectoryReader();

        $feedPath = $mediaDirectoryReader->getAbsolutePath($this->feedDirectory)
            . '/'
            . $store->getFeedFileNameBase()
            . '.xml';

        return file_exists($feedPath);
    }
}
