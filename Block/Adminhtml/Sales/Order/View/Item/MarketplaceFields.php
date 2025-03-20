<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Sales\Order\View\Item;

use Magento\Backend\Block\Template;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\ItemInterface;

class MarketplaceFields extends Template
{
    const FORMAT_LINK = 'link';
    const FORMAT_TEXT = 'text';

    /**
     * @return \Magento\Sales\Model\Order\Item
     */
    public function getItem()
    {
        return $this->getParentBlock()->getData('item');
    }

    /**
     * @return string[]
     */
    public function getDisplayableFieldNames()
    {
        return [
            ItemInterface::ADDITIONAL_FIELD_ARTICLE_ID,
            ItemInterface::ADDITIONAL_FIELD_ORDER_ITEM_ID,
            ItemInterface::ADDITIONAL_FIELD_CUSTOMIZED_URL,
        ];
    }

    /**
     * @param string $field
     * @return string
     */
    public function getFieldFormat($field)
    {
        return ($field === ItemInterface::ADDITIONAL_FIELD_CUSTOMIZED_URL)
            ? self::FORMAT_LINK
            : self::FORMAT_TEXT;
    }

    /**
     * @return array
     */
    public function getMarketplaceFields()
    {
        if (
            ($item = $this->getItem())
            && ($fields = $item->getProductOptionByCode(ItemInterface::ORDER_ITEM_OPTION_CODE_ADDITIONAL_FIELDS))
            && is_array($fields)
        ) {
            ksort($fields);
            $displayableFieldNames = $this->getDisplayableFieldNames();

            foreach ($fields as $key => $value) {
                if (!is_scalar($value) || !in_array($key, $displayableFieldNames, true)) {
                    unset($fields[$key]);
                }
            }

            return $fields;
        }

        return [];
    }
}
