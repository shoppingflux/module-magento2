<?php

namespace ShoppingFeed\Manager\Model\Feed;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem as FileSystem;
use Magento\Framework\Filesystem\Directory\ReadInterface as DirectoryReadInterface;
use ShoppingFeed\Feed\Product\Product as ExportedProduct;
use ShoppingFeed\Feed\ProductGeneratorFactory as FeedGeneratorFactory;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\ConfigInterface as FeedConfigInterface;
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

    /**
     * @var ExporterResource
     */
    private $resource;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetaData;

    /**
     * @var DirectoryReadInterface
     */
    private $directoryReader;

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
     * @var string
     */
    private $feedBaseFileName;

    /**
     * @param FileSystem $fileSystem
     * @param ExporterResource $resource
     * @param ProductMetadataInterface $productMetadata
     * @param FeedGeneratorFactory $feedGeneratorFactory
     * @param ConfigInterface $generalConfig
     * @param ExportStateConfigInterface $exportStateConfig
     * @param SectionTypePoolInterface $sectionTypePool
     * @param string $feedDirectory
     * @param string $feedBaseFileName
     */
    public function __construct(
        FileSystem $fileSystem,
        ExporterResource $resource,
        ProductMetadataInterface $productMetadata,
        FeedGeneratorFactory $feedGeneratorFactory,
        FeedConfigInterface $generalConfig,
        ExportStateConfigInterface $exportStateConfig,
        SectionTypePoolInterface $sectionTypePool,
        $feedDirectory,
        $feedBaseFileName
    ) {
        $this->resource = $resource;
        $this->productMetaData = $productMetadata;
        $this->directoryReader = $fileSystem->getDirectoryRead(DirectoryList::MEDIA);
        $this->feedGeneratorFactory = $feedGeneratorFactory;
        $this->generalConfig = $generalConfig;
        $this->exportStateConfig = $exportStateConfig;
        $this->sectionTypePool = $sectionTypePool;
        $this->feedDirectory = $feedDirectory;
        $this->feedBaseFileName = $feedBaseFileName;
    }

    /**
     * @param StoreInterface $store
     * @param ExportableProduct $product
     * @param bool $adaptAsRetained
     * @param bool $adaptAsParent
     * @param bool $adaptAsChild
     */
    private function adaptExportableProductSectionsData(
        StoreInterface $store,
        ExportableProduct $product,
        $adaptAsRetained,
        $adaptAsParent = false,
        $adaptAsChild = false
    ) {
        foreach ($this->sectionTypePool->getTypes() as $sectionType) {
            $typeAdapter = $sectionType->getAdapter();
            $typeId = $sectionType->getId();
            $productData = (array) $product->getSectionTypeData($typeId);

            if ($adaptAsParent) {
                $productData = $typeAdapter->adaptParentProductData(
                    $store,
                    $productData,
                    $product->getChildrenSectionTypeData($typeId)
                );
            } elseif ($adaptAsChild) {
                $productData = $typeAdapter->adaptChildProductData($store, $productData);
            }

            if ($adaptAsRetained) {
                $productData = $typeAdapter->adaptRetainedProductData($store, $productData);
            }

            $product->setSectionTypeData($typeId, $productData);
        }
    }

    /**
     * @param StoreInterface $store
     * @throws \Exception
     */
    public function exportStoreFeed(StoreInterface $store)
    {
        $feedMediaPath = $this->directoryReader->getAbsolutePath($this->feedDirectory) . '/';
        $feedFileName = sprintf($this->feedBaseFileName, $store->getId());
        $feedFilePath = $feedMediaPath . $feedFileName;
        $feedTempFilePath = $feedFilePath . '.tmp';

        $feedGenerator = $this->feedGeneratorFactory->create();
        $childrenExportMode = $this->exportStateConfig->getChildrenExportMode($store);

        $feedGenerator->setPlatform(
            $this->productMetaData->getName() . ' ' . $this->productMetaData->getEdition(),
            $this->productMetaData->getVersion()
        );

        $baseStore = $store->getBaseStore();
        $feedGenerator->setAttribute('storeName', $baseStore->getName());
        $feedGenerator->setAttribute('storeUrl', $baseStore->getUrl());

        if ($this->generalConfig->shouldUseGzipCompression($store)) {
            $feedFilePath .= '.gz';
            $feedTempFilePath .= '.gz';
            $feedGenerator->setUri('compress.zlib://' . $feedTempFilePath);
        } else {
            $feedGenerator->setUri('file://' . $feedTempFilePath);
        }

        $feedGenerator->addProcessor(
            function (ExportableProduct $product) use ($store) {
                $isParent = $product->hasChildren();
                $isRetained = (FeedProduct::STATE_RETAINED === $product->getExportState());

                if ($isParent || $isRetained) {
                    $this->adaptExportableProductSectionsData($store, $product, $isRetained, $isParent);
                }

                if ($isParent) {
                    foreach ($product->getChildren() as $childProduct) {
                        $this->adaptExportableProductSectionsData(
                            $store,
                            $childProduct,
                            $isRetained,
                            false,
                            true
                        );
                    }
                }

                return $product;
            }
        );

        $feedGenerator->addMapper(
            function (ExportableProduct $product, ExportedProduct $exportedProduct) use ($store) {
                foreach ($this->sectionTypePool->getTypes() as $sectionType) {
                    $sectionType->getAdapter()->exportBaseProductData(
                        $store,
                        $product->getSectionTypeData($sectionType->getId()),
                        $exportedProduct
                    );

                    $sectionType->getAdapter()->exportMainProductData(
                        $store,
                        $product->getSectionTypeData($sectionType->getId()),
                        $exportedProduct
                    );
                }

                if ($product->hasChildren()) {
                    $configurableAttributeCodes = $product->getConfigurableAttributeCodes();

                    foreach ($product->getChildren() as $childProduct) {
                        $exportedVariation = $exportedProduct->createVariation();

                        foreach ($this->sectionTypePool->getTypes() as $sectionType) {
                            $sectionType->getAdapter()->exportBaseProductData(
                                $store,
                                $childProduct->getSectionTypeData($sectionType->getId()),
                                $exportedVariation
                            );

                            $sectionType->getAdapter()->exportVariationProductData(
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

        $productIterators = new \AppendIterator();

        $productIterators->append(
            $this->resource->getExportableProductsIterator(
                $store->getId(),
                $this->sectionTypePool->getTypeIds(),
                [ FeedProduct::STATE_EXPORTED, FeedProduct::STATE_RETAINED ],

                in_array(
                    $childrenExportMode,
                    [ self::CHILDREN_EXPORT_MODE_NONE, self::CHILDREN_EXPORT_MODE_SEPARATELY ],
                    true
                ),

                in_array(
                    $childrenExportMode,
                    [ self::CHILDREN_EXPORT_MODE_BOTH, self::CHILDREN_EXPORT_MODE_SEPARATELY ],
                    true
                )
            )
        );

        if ((self::CHILDREN_EXPORT_MODE_BOTH === $childrenExportMode)
            || (self::CHILDREN_EXPORT_MODE_WITHIN_PARENTS === $childrenExportMode)
        ) {
            $productIterators->append(
                $this->resource->getExportableParentProductsIterator(
                    $store->getId(),
                    $this->sectionTypePool->getTypeIds(),
                    [ FeedProduct::STATE_EXPORTED, FeedProduct::STATE_RETAINED ],
                    [ FeedProduct::STATE_EXPORTED ]
                )
            );
        }

        $feedGenerator->write($productIterators);

        if (false === rename($feedTempFilePath, $feedFilePath)) {
            throw new LocalizedException(
                __('Could not copy file "%1" to file "%2".', $feedTempFilePath, $feedFilePath)
            );
        }
    }
}
