<?php

namespace ShoppingFeed\Manager\Model\Marketplace\Order\Notification;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\UrlInterface;
use ShoppingFeed\Manager\Api\Data\Account\StoreInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\OrderInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Account\Store\CollectionFactory as StoreCollectionFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\CollectionFactory as OrderCollectionFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Grid\Collection as OrderGridCollection;
use ShoppingFeed\Manager\Model\Sales\Order\ConfigInterface as OrderConfigInterface;

class UnimportedOrders implements MessageInterface
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::marketplace_order_import';

    const CACHE_KEY = 'sfm_unimported_marketplace_order_ids';

    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var StoreCollectionFactory
     */
    private $storeCollectionFactory;

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var OrderConfigInterface
     */
    private $orderGeneralConfig;

    /**
     * @var int[]|null
     */
    private $unimportedOrderIds = null;

    /**
     * @param AuthorizationInterface $authorization
     * @param SerializerInterface $serializer
     * @param CacheInterface $cache
     * @param UrlInterface $urlBuilder
     * @param StoreCollectionFactory $storeCollectionFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param OrderConfigInterface $orderGeneralConfig
     */
    public function __construct(
        AuthorizationInterface $authorization,
        SerializerInterface $serializer,
        CacheInterface $cache,
        UrlInterface $urlBuilder,
        StoreCollectionFactory $storeCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        OrderConfigInterface $orderGeneralConfig
    ) {
        $this->authorization = $authorization;
        $this->serializer = $serializer;
        $this->cache = $cache;
        $this->urlBuilder = $urlBuilder;
        $this->storeCollectionFactory = $storeCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderGeneralConfig = $orderGeneralConfig;
    }

    public function getIdentity()
    {
        return hash('sha256', 'sfm_unimported_marketplace_orders');
    }

    /**
     * @param OrderInterface $order
     * @param StoreInterface $store
     * @return bool
     */
    private function isImportableStoreOrder(OrderInterface $order, StoreInterface $store)
    {
        if (!empty($order->getSalesOrderId())) {
            return false;
        }

        $createdAt = \DateTime::createFromFormat('Y-m-d H:i:s', $order->getCreatedAt());

        if ($createdAt < $this->orderGeneralConfig->getOrderImportFromDate($store)) {
            return false;
        }

        $isShipped = ($order->getShoppingFeedStatus() === OrderInterface::STATUS_SHIPPED);

        if ($order->isFulfilled()) {
            return $isShipped && $this->orderGeneralConfig->shouldImportFulfilledOrders($store);
        }

        if ($isShipped) {
            return $this->orderGeneralConfig->shouldImportShippedOrders($store);
        }

        return ($order->getShoppingFeedStatus() === OrderInterface::STATUS_WAITING_SHIPMENT);
    }

    /**
     * @return int[]
     */
    private function getUnimportedOrderIds()
    {
        if (null === $this->unimportedOrderIds) {
            $cache = $this->cache->load(static::CACHE_KEY);

            if (is_string($cache) && ('' !== $cache)) {
                try {
                    $orderIds = $this->serializer->unserialize($cache);
                } catch (\Exception $e) {
                    $orderIds = null;
                }

                if (is_array($orderIds)) {
                    $this->unimportedOrderIds = array_map('intval', $orderIds);
                }
            }
        }

        if (null === $this->unimportedOrderIds) {
            $this->unimportedOrderIds = [];

            $storeLimitDates = [];
            $storeCollection = $this->storeCollectionFactory->create();

            foreach ($storeCollection as $store) {
                if (!$this->orderGeneralConfig->shouldImportOrders($store)) {
                    continue;
                }

                $storeLimitDates[$store->getId()] = $this->orderGeneralConfig->getOrderImportFromDate($store);
            }

            $orderCollection = $this->orderCollectionFactory->create();
            $orderCollection->addNonImportedFilter();
            $orderCollection->addStoreCreatedFromDatesFilter($storeLimitDates);

            /** @var OrderInterface $order */
            foreach ($orderCollection as $order) {
                $store = $storeCollection->getItemById($order->getStoreId());

                if (
                    (null !== $store)
                    && ($order->getImportRemainingTryCount() === 0)
                    && $this->isImportableStoreOrder($order, $store)
                ) {
                    $this->unimportedOrderIds[] = $order->getId();
                }
            }

            $this->cache->save(
                $this->serializer->serialize($this->unimportedOrderIds),
                static::CACHE_KEY,
                [],
                3600
            );
        }

        return $this->unimportedOrderIds;
    }

    public function isDisplayed()
    {
        return $this->authorization->isAllowed(static::ADMIN_RESOURCE) && !empty($this->getUnimportedOrderIds());
    }

    public function getText()
    {
        return implode(
            ' ',
            [
                'Shopping Feed:',
                __(
                    '<strong>%1</strong> importable marketplace orders could not be imported.',
                    count($this->getUnimportedOrderIds())
                ),
                __(
                    '<a href="%1">Click here to view the orders</a>.',
                    $this->urlBuilder->getUrl(
                        'shoppingfeed_manager/marketplace_order/index',
                        [
                            OrderInterface::ORDER_ID =>
                                implode('_', array_slice($this->getUnimportedOrderIds(), 0, 100)),
                            OrderGridCollection::FIELD_IS_IMPORTED =>
                                OrderGridCollection::IS_IMPORTED_FILTER_VALUE_UNIMPORTED,
                        ]
                    )
                ),
            ]
        );
    }

    public function getSeverity()
    {
        return self::SEVERITY_MAJOR;
    }
}
