<?php

namespace ShoppingFeed\Manager\Model\Feed\Product\Attribute\Value;

use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Catalog\Model\Product\Attribute\Frontend\Image as ImageFrontend;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend;
use Magento\Eav\Model\Entity\Attribute\Backend\Datetime as DateTimeBackend;
use Magento\Catalog\Model\Product\Attribute\Backend\Price as PriceBackend;
use Magento\Framework\Exception\LocalizedException;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;

abstract class AbstractRenderer implements RendererInterface
{
    /**
     * @param mixed $value
     * @return bool
     */
    protected function isUndefinedValue($value)
    {
        return (null === $value) || ('' === $value);
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected function isDefinedValue($value)
    {
        return !$this->isUndefinedValue($value);
    }

    /**
     * @param AbstractAttribute $attribute
     * @return string|null
     */
    protected function getAttributeDefaultValue(AbstractAttribute $attribute)
    {
        return null;
    }

    /**
     * @param StoreInterface $store
     * @param AbstractAttribute $attribute
     * @param mixed $value
     * @return string|array
     */
    protected function renderAttributeValue(StoreInterface $store, AbstractAttribute $attribute, $value)
    {
        return (string) $value;
    }

    public function getProductAttributeValue(
        StoreInterface $store,
        CatalogProduct $product,
        AbstractAttribute $attribute
    ) {
        $attributeCode = $attribute->getAttributeCode();
        return !$product->hasData($attributeCode)
            ? $this->getAttributeDefaultValue($attribute)
            : $this->renderAttributeValue($store, $attribute, $product->getData($attributeCode));
    }

    /**
     * @param AbstractAttribute $attribute
     * @return bool
     */
    protected function isBooleanAttribute(AbstractAttribute $attribute)
    {
        return ($attribute->getFrontendInput() === 'boolean');
    }

    /**
     * @param AbstractAttribute $attribute
     * @return bool
     */
    protected function isDateAttribute(AbstractAttribute $attribute)
    {
        try {
            return in_array($attribute->getAttributeCode(), [ 'created_at', 'updated_at' ], true)
                || (($attribute->getFrontendInput() === 'date')
                    && ($attribute->getBackend() instanceof DateTimeBackend));
        } catch (LocalizedException $e) {
            return false;
        }
    }

    /**
     * @param AbstractAttribute $attribute
     * @return bool
     */
    protected function isDecimalAttribute(AbstractAttribute $attribute)
    {
        return ('decimal' === $attribute->getBackendType());
    }

    /**
     * @param AbstractAttribute $attribute
     * @return bool
     */
    protected function isImageAttribute(AbstractAttribute $attribute)
    {
        return ($attribute->getFrontend() instanceof ImageFrontend);
    }

    /**
     * @param AbstractAttribute $attribute
     * @return bool
     */
    protected function isMultipleValuesAttribute(AbstractAttribute $attribute)
    {
        try {
            return ($attribute->getFrontendInput() === 'multiselect')
                || ($attribute->getBackend() instanceof ArrayBackend);
        } catch (LocalizedException $e) {
            return false;
        }
    }

    /**
     * @param AbstractAttribute $attribute
     * @return bool
     */
    protected function isOptionAttribute(AbstractAttribute $attribute)
    {
        return in_array($attribute->getFrontendInput(), [ 'select', 'multiselect' ], true);
    }

    /**
     * @param AbstractAttribute $attribute
     * @return bool
     */
    public function isPriceAttribute(AbstractAttribute $attribute)
    {
        try {
            return $attribute->getBackend() instanceof PriceBackend;
        } catch (LocalizedException $e) {
            return false;
        }
    }

    /**
     * @param AbstractAttribute $attribute
     * @return bool
     */
    public function isTextAttribute(AbstractAttribute $attribute)
    {
        if ($attribute->getAttributeCode() === 'sku') {
            return true;
        }

        return in_array($attribute->getBackendType(), [ 'text', 'varchar' ], true)
            && in_array($attribute->getFrontendInput(), [ 'text', 'textarea' ], true);
    }
}
