<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Adapter;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\DataObject;
use ShoppingFeed\Feed\Product\AbstractProduct as AbstractExportedProduct;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Constants as ProductConstants;
use ShoppingFeed\Manager\Model\Feed\Product\Section\AbstractAdapter;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Config\ImagesInterface as ConfigInterface;
use ShoppingFeed\Manager\Model\Feed\Product\Section\Type\Images as Type;
use ShoppingFeed\Manager\Model\Feed\RefreshableProduct;

/**
 * @method ConfigInterface getConfig()
 */
class Images extends AbstractAdapter implements ImagesInterface
{
    const DEFAULT_MAXIMUM_IMAGE_COUNT = 20;

    const KEY_MAIN_IMAGE = 'main_image';
    const KEY_ADDITIONAL_IMAGES = 'additional_images';

    public function getSectionType()
    {
        return Type::CODE;
    }

    public function requiresLoadedProduct(StoreInterface $store)
    {
        return true;
    }

    public function prepareLoadableProductCollection(StoreInterface $store, ProductCollection $productCollection)
    {
        $productCollection->addAttributeToSelect('image');
    }

    /**
     * @param StoreInterface $store
     * @return int
     */
    private function getMaximumImageCount(StoreInterface $store)
    {
        $config = $this->getConfig();
        return $config->shouldExportAllImages($store)
            ? self::DEFAULT_MAXIMUM_IMAGE_COUNT
            : $config->getExportedImageCount($store);
    }

    public function getProductData(StoreInterface $store, RefreshableProduct $product)
    {
        $data = [];
        $catalogProduct = $product->getCatalogProduct();
        $mainImageFile = trim($catalogProduct->getImage());
        $totalImageCount = 1;
        $imageUrls = [];
        $imagePositions = [];
        $galleryImages = $catalogProduct->getMediaGalleryImages();

        if ($galleryImages instanceof \Traversable) {
            /** @var DataObject $galleryImage */
            foreach ($galleryImages as $galleryImage) {
                $imageFile = trim($galleryImage->getData('file'));

                if (!empty($imageFile) && (ProductConstants::EMPTY_IMAGE_VALUE !== $imageFile)) {
                    $imageUrls[$imageFile] = trim($galleryImage->getData('url'));
                    $imagePositions[$imageFile] = (int) $galleryImage->getData('position');
                }
            }

            if (isset($imagePositions[$mainImageFile])) {
                $imagePositions[$mainImageFile] = PHP_INT_MIN;
            }

            asort($imagePositions, SORT_NUMERIC);

            $isAdditionalImage = false;
            $data[self::KEY_ADDITIONAL_IMAGES] = [];
            $maximumImageCount = $this->getMaximumImageCount($store);

            foreach ($imagePositions as $imageFile => $imagePosition) {
                if ($isAdditionalImage) {
                    $data[self::KEY_ADDITIONAL_IMAGES][] = $imageUrls[$imageFile];
                } else {
                    $data[self::KEY_MAIN_IMAGE] = $imageUrls[$imageFile];
                    $isAdditionalImage = true;
                }

                if (++$totalImageCount >= $maximumImageCount) {
                    break;
                }
            }

            if (empty($data[self::KEY_ADDITIONAL_IMAGES])) {
                unset($data[self::KEY_ADDITIONAL_IMAGES]);
            }
        }

        return $data;
    }

    public function exportBaseProductData(
        StoreInterface $store,
        array $productData,
        AbstractExportedProduct $exportedProduct
    ) {
        if (isset($productData[self::KEY_MAIN_IMAGE])) {
            $exportedProduct->setMainImage($productData[self::KEY_MAIN_IMAGE]);
        }

        if (isset($productData[self::KEY_ADDITIONAL_IMAGES])) {
            $exportedProduct->setAdditionalImages($productData[self::KEY_ADDITIONAL_IMAGES]);
        }
    }
}
