<?php

namespace ShoppingFeed\Manager\Block\Adminhtml\Sales\Order\View\Item;

use Magento\Backend\Block\Template;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\ItemInterface;

class MarketplaceFields extends Template
{
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
            'article_id',
            'order_item_id',
        ];
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
