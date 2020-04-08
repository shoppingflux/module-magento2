<?php

namespace ShoppingFeed\Manager\Ui\Component\Listing\Column\Marketplace\Order;

use Magento\Ui\Component\Listing\Columns\Column as Column;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface;

class Importable extends Column
{
    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');

            foreach ($dataSource['data']['items'] as &$item) {
                $item[$fieldName] = 0;

                if (array_key_exists(OrderInterface::SALES_ORDER_ID, $item)
                    && empty($item[OrderInterface::SALES_ORDER_ID])
                    && isset($item[OrderInterface::SHOPPING_FEED_STATUS])
                    && (OrderInterface::STATUS_WAITING_SHIPMENT === $item[OrderInterface::SHOPPING_FEED_STATUS])
                ) {
                    $item[$fieldName] = 1;
                }
            }
        }

        return $dataSource;
    }
}
