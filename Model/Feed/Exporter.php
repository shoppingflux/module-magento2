<?php

namespace ShoppingFeed\Manager\Model\Feed;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem as FileSystem;
use Magento\Framework\Filesystem\Directory\Write as DirectoryWriter;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\ExportableProduct;
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
     * @var DirectoryWriter
     */
    private $fileWriter;

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
     * @param ExportStateConfigInterface $exportStateConfig
     * @param SectionTypePoolInterface $sectionTypePool
     * @param string $feedDirectory
     * @param string $feedBaseFileName
     * @throws FileSystemException
     */
    public function __construct(
        FileSystem $fileSystem,
        ExporterResource $resource,
        ExportStateConfigInterface $exportStateConfig,
        SectionTypePoolInterface $sectionTypePool,
        $feedDirectory,
        $feedBaseFileName
    ) {
        $this->fileWriter = $fileSystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->resource = $resource;
        $this->exportStateConfig = $exportStateConfig;
        $this->sectionTypePool = $sectionTypePool;
        $this->feedDirectory = $feedDirectory;
        $this->feedBaseFileName = $feedBaseFileName;
    }

    /**
     * @param ExportableProduct $product
     * @param \DOMDocument $domDocument
     * @param string $nodeName
     * @return \DOMElement
     */
    private function createExportableProductNode(
        ExportableProduct $product,
        \DOMDocument $domDocument,
        $nodeName = 'product'
    ) {
        $productNode = $domDocument->createElement($nodeName);
        $sectionsData = $product->getMergedSectionsData();

        foreach ($sectionsData as $key => $value) {
            $valueNode = $domDocument->createElement($key);
            $valueNode->appendChild($domDocument->createCDATASection($value));
            $productNode->appendChild($valueNode);
        }

        return $productNode;
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
     * @return $this
     * @throws FileSystemException
     * @throws \Zend_Db_Statement_Exception
     */
    public function exportStoreFeed(StoreInterface $store)
    {
        $feedMediaPath = $this->fileWriter->getAbsolutePath($this->feedDirectory) . '/';
        $this->fileWriter->create($feedMediaPath);

        $feedFileName = sprintf($this->feedBaseFileName, $store->getId());
        $feedTempFileName = $feedFileName . '.tmp';
        $feedFilePath = $feedMediaPath . $feedFileName;
        $feedTempFilePath = $feedMediaPath . $feedTempFileName;

        $fileDriver = $this->fileWriter->getDriver();
        $feedTempFile = $fileDriver->fileOpen($feedTempFilePath, 'w');

        $fileDriver->fileWrite($feedTempFile, '<?xml version="1.0" encoding="utf-8"?>');
        $fileDriver->fileWrite($feedTempFile, "\n");
        $fileDriver->fileWrite($feedTempFile, '<products>');

        $domDocument = new \DOMDocument('1.0', 'utf-8');
        $domDocument->formatOutput = true;

        $childrenExportMode = $this->exportStateConfig->getChildrenExportMode($store);

        $this->resource->iterateExportableProducts(
            function (ExportableProduct $product) use ($store, $fileDriver, $feedTempFile, $domDocument) {
                if (FeedProduct::STATE_RETAINED === $product->getExportState()) {
                    $this->adaptExportableProductSectionsData($store, $product, true);
                }

                $productNode = $this->createExportableProductNode($product, $domDocument);
                $fileDriver->fileWrite($feedTempFile, "\n" . $domDocument->saveXML($productNode));
            },
            $store->getId(),
            $this->sectionTypePool->getTypeIds(),
            [ FeedProduct::STATE_EXPORTED, FeedProduct::STATE_RETAINED ],
            (self::CHILDREN_EXPORT_MODE_NONE === $childrenExportMode)
            || (self::CHILDREN_EXPORT_MODE_SEPARATELY === $childrenExportMode),
            (self::CHILDREN_EXPORT_MODE_BOTH === $childrenExportMode)
            || (self::CHILDREN_EXPORT_MODE_SEPARATELY === $childrenExportMode)
        );

        if ((self::CHILDREN_EXPORT_MODE_BOTH === $childrenExportMode)
            || (self::CHILDREN_EXPORT_MODE_WITHIN_PARENTS === $childrenExportMode)
        ) {
            $this->resource->iterateExportableParentProducts(
                function (ExportableProduct $parentProduct) use ($store, $fileDriver, $feedTempFile, $domDocument) {
                    $isRetainedParent = (FeedProduct::STATE_RETAINED === $parentProduct->getExportState());
                    $childNodes = [];

                    foreach ($parentProduct->getChildren() as $childProduct) {
                        $this->adaptExportableProductSectionsData(
                            $store,
                            $childProduct,
                            $isRetainedParent,
                            false,
                            true
                        );

                        $childNodes[] = $this->createExportableProductNode($childProduct, $domDocument, 'child');
                    }

                    $this->adaptExportableProductSectionsData($store, $parentProduct, $isRetainedParent, true);
                    $parentNode = $this->createExportableProductNode($parentProduct, $domDocument);
                    $childrenNode = $domDocument->createElement('child-products');

                    foreach ($childNodes as $childNode) {
                        $childrenNode->appendChild($childNode);
                    }

                    $parentNode->appendChild($childrenNode);
                    $fileDriver->fileWrite($feedTempFile, "\n" . $domDocument->saveXML($parentNode));
                },
                $store->getId(),
                $this->sectionTypePool->getTypeIds(),
                [ FeedProduct::STATE_EXPORTED, FeedProduct::STATE_RETAINED ],
                [ FeedProduct::STATE_EXPORTED ]
            );
        }

        $fileDriver->fileWrite($feedTempFile, "\n");
        $fileDriver->fileWrite($feedTempFile, '</products>');
        $fileDriver->fileClose($feedTempFile);
        $this->fileWriter->renameFile($feedTempFilePath, $feedFilePath);

        return $this;
    }
}
