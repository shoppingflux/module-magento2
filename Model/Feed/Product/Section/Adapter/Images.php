<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Section\Adapter;

use Magento\Framework\DataObject;
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

    const BASE_KEY_IMAGE_URL = 'image_url_%d';
    const BASE_KEY_IMAGE_LABEL = 'image_label_%d';

    public function getSectionType()
    {
        return Type::CODE;
    }

    public function getProductData(StoreInterface $store, RefreshableProduct $product)
    {
        $data = [];
        $config = $this->getConfig();
        $catalogProduct = $product->getCatalogProduct();
        $mainImageFile = trim($catalogProduct->getImage());
        $totalImageCount = 1;
        $imageUrls = [];
        $imageLabels = [];
        $imagePositions = [];

        /** @var DataObject $galleryImage */
        foreach ($catalogProduct->getMediaGalleryImages() as $galleryImage) {
            $imageFile = trim($galleryImage->getData('file'));

            if (!empty($imageFile) && (ProductConstants::EMPTY_IMAGE_VALUE !== $imageFile)) {
                $imageUrls[$imageFile] = trim($galleryImage->getData('url'));
                $imageLabels[$imageFile] = trim($galleryImage->getData('label'));
                $imagePositions[$imageFile] = (int) $galleryImage->getData('position');
            }
        }

        if (isset($imagePositions[$mainImageFile])) {
            $imagePositions[$mainImageFile] = PHP_INT_MIN;
        }

        asort($imagePositions, SORT_NUMERIC);

        $maximumImageCount = $config->shouldExportAllImages($store)
            ? self::DEFAULT_MAXIMUM_IMAGE_COUNT
            : $config->getExportedImageCount($store);

        foreach ($imagePositions as $imageFile => $imagePosition) {
            $data[sprintf(self::BASE_KEY_IMAGE_URL, $totalImageCount)] = $imageUrls[$imageFile];
            $data[sprintf(self::BASE_KEY_IMAGE_LABEL, $totalImageCount)] = $imageLabels[$imageFile];

            if (++$totalImageCount >= $maximumImageCount) {
                break;
            }
        }

        return $data;
    }
}
