<?php

namespace ShoppingFeed\Manager\Ui\Component\Listing\Column\Marketplace\Order;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column as Column;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface;
use ShoppingFeed\Manager\Model\Sales\Order\ConfigInterface as OrderConfigInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\Collection as StoreCollection;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\CollectionFactory as StoreCollectionFactory;

abstract class AbstractColumn extends Column
{
    /**
     * @var StoreCollectionFactory
     */
    private $storeCollectionFactory;

    /**
     * @var StoreCollection|null
     */
    static private $storeCollection = null;

    /**
     * @var OrderConfigInterface
     */
    private $orderGeneralConfig;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param StoreCollectionFactory $storeCollectionFactory
     * @param OrderConfigInterface $orderGeneralConfig
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        StoreCollectionFactory $storeCollectionFactory,
        OrderConfigInterface $orderGeneralConfig,
        array $components = [],
        array $data = []
    ) {
        $this->storeCollectionFactory = $storeCollectionFactory;
        $this->orderGeneralConfig = $orderGeneralConfig;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @return StoreCollection
     */
    private function getStoreCollection()
    {
        if (null === self::$storeCollection) {
            self::$storeCollection = $this->storeCollectionFactory->create();
            self::$storeCollection->load();
        }

        return self::$storeCollection;
    }

    /**
     * @param int $storeId
     * @param string $shoppingFeedStatus
     * @param bool $isFulfilled
     * @param int $salesOrderId
     * @param \DateTime $createdAt
     * @return bool
     */
    private function isImportableOrderData(
        $storeId,
        $shoppingFeedStatus,
        $isFulfilled,
        $salesOrderId,
        \DateTime $createdAt
    ) {
        if (!empty($salesOrderId)) {
            return false;
        }

        /** @var StoreInterface $store */
        $store = $this->getStoreCollection()->getItemById($storeId);

        if (!$store instanceof StoreInterface) {
            return false;
        }

        if ($createdAt < $this->orderGeneralConfig->getOrderImportFromDate($store)) {
            return false;
        }

        $isShipped = ($shoppingFeedStatus === OrderInterface::STATUS_SHIPPED);

        if ($isFulfilled) {
            return $isShipped && $this->orderGeneralConfig->shouldImportFulfilledOrders($store);
        }

        if ($isShipped) {
            return $this->orderGeneralConfig->shouldImportShippedOrders($store);
        }

        return (OrderInterface::STATUS_WAITING_SHIPMENT === $shoppingFeedStatus);
    }

    /**
     * @param array $item
     * @return bool|null
     */
    public function isImportableOrderItem(array $item)
    {
        if (
            isset($item[OrderInterface::STORE_ID])
            && isset($item[OrderInterface::SHOPPING_FEED_STATUS])
            && isset($item[OrderInterface::IS_FULFILLED])
            && array_key_exists(OrderInterface::SALES_ORDER_ID, $item)
            && isset($item[OrderInterface::CREATED_AT])
        ) {
            return $this->isImportableOrderData(
                (int) $item[OrderInterface::STORE_ID],
                trim($item[OrderInterface::SHOPPING_FEED_STATUS]),
                (bool) $item[OrderInterface::IS_FULFILLED],
                (int) $item[OrderInterface::SALES_ORDER_ID],
                \DateTime::createFromFormat('Y-m-d H:i:s', $item[OrderInterface::CREATED_AT])
            );
        }

        return null;
    }
}
