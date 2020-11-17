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
     * @param int $storeId
     * @param string $shoppingFeedStatus
     * @param bool $isFulfilled
     * @param int $salesOrderId
     * @param \DateTime $createdAt
     * @return string|null
     */
    private function getNonImportableOrderDataReason(
        $storeId,
        $shoppingFeedStatus,
        $isFulfilled,
        $salesOrderId,
        \DateTime $createdAt
    ) {
        if (!empty($salesOrderId)) {
            return __('already imported');
        }

        /** @var StoreInterface $store */
        $store = $this->getStoreCollection()->getItemById($storeId);

        if (!$store instanceof StoreInterface) {
            return __('unexisting account');
        }

        if ($createdAt < $this->orderGeneralConfig->getOrderImportFromDate($store)) {
            return __('too old');
        }

        $isShipped = ($shoppingFeedStatus === OrderInterface::STATUS_SHIPPED);

        if ($isFulfilled) {
            if (!$this->orderGeneralConfig->shouldImportFulfilledOrders($store)) {
                return __('import of fulfilled orders is disabled');
            } elseif (!$isShipped) {
                return __('status is not "shipped"');
            }
        }

        if ($isShipped) {
            if (!$this->orderGeneralConfig->shouldImportShippedOrders($store)) {
                return __('import of shipped orders is disabled');
            }
        }

        if (OrderInterface::STATUS_WAITING_SHIPMENT !== $shoppingFeedStatus) {
            return __('status is not "waiting_shipment"');
        }

        return null;
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

    /**
     * @param array $item
     * @return string
     */
    public function getOrderItemImportableStatus(array $item)
    {
        if (
            isset($item[OrderInterface::STORE_ID])
            && isset($item[OrderInterface::SHOPPING_FEED_STATUS])
            && isset($item[OrderInterface::IS_FULFILLED])
            && array_key_exists(OrderInterface::SALES_ORDER_ID, $item)
            && isset($item[OrderInterface::CREATED_AT])
        ) {
            $isImportable = $this->isImportableOrderData(
                (int) $item[OrderInterface::STORE_ID],
                trim($item[OrderInterface::SHOPPING_FEED_STATUS]),
                (bool) $item[OrderInterface::IS_FULFILLED],
                (int) $item[OrderInterface::SALES_ORDER_ID],
                \DateTime::createFromFormat('Y-m-d H:i:s', $item[OrderInterface::CREATED_AT])
            );

            if ($isImportable) {
                return (string) __('Yes');
            }

            $reason = $this->getNonImportableOrderDataReason(
                (int) $item[OrderInterface::STORE_ID],
                trim($item[OrderInterface::SHOPPING_FEED_STATUS]),
                (bool) $item[OrderInterface::IS_FULFILLED],
                (int) $item[OrderInterface::SALES_ORDER_ID],
                \DateTime::createFromFormat('Y-m-d H:i:s', $item[OrderInterface::CREATED_AT])
            );

            return (string) __('No (%1)', $reason);
        }

        return (string) __('Undetermined (missing data)');
    }
}
