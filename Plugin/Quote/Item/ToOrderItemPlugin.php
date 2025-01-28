<?php

namespace ShoppingFeed\Manager\Plugin\Quote\Item;

use Magento\Quote\Model\Quote\Address\Item as QuoteAddressItem;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote\Item\ToOrderItem;
use Magento\Sales\Api\Data\OrderItemInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\ItemInterface as MarketplaceItemInterface;

class ToOrderItemPlugin
{
    /**
     * @param ToOrderItem $subject
     * @param OrderItemInterface $result
     * @param QuoteItem|QuoteAddressItem $item
     * @param array $data
     * @return OrderItemInterface
     */
    public function afterConvert(ToOrderItem $subject, $result, $item, $data = [])
    {
        if (
            ($result instanceof OrderItemInterface)
            && is_array($options = $result->getProductOptions())
            && ($fields = $item->getOptionByCode(MarketplaceItemInterface::ORDER_ITEM_OPTION_CODE_ADDITIONAL_FIELDS))
            && is_array($fields = json_decode($fields->getValue(), true))
        ) {
            $options[MarketplaceItemInterface::ORDER_ITEM_OPTION_CODE_ADDITIONAL_FIELDS] = $fields;
            $result->setProductOptions($options);
        }

        return $result;
    }
}