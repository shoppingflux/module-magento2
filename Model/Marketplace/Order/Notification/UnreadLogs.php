<?php

namespace ShoppingFeed\Manager\Model\Marketplace\Order\Notification;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\UrlInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\LogInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Log\CollectionFactory as LogCollectionFactory;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Log\Grid\Collection as LogGridCollection;

class UnreadLogs implements MessageInterface
{
    const ADMIN_RESOURCE = 'ShoppingFeed_Manager::marketplace_order_logs';

    const CACHE_KEY = 'sfm_unread_marketplace_order_log_counts';

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
     * @var LogCollectionFactory
     */
    private $logCollectionFactory;

    /**
     * @var int|null
     */
    private $unreadLogsCount = null;

    /**
     * @var int|null
     */
    private $ordersWithUnreadLogsCount = null;

    /**
     * @param AuthorizationInterface $authorization
     * @param SerializerInterface $serializer
     * @param CacheInterface $cache
     * @param UrlInterface $urlBuilder
     * @param LogCollectionFactory $logCollectionFactory
     */
    public function __construct(
        AuthorizationInterface $authorization,
        SerializerInterface $serializer,
        CacheInterface $cache,
        UrlInterface $urlBuilder,
        LogCollectionFactory $logCollectionFactory
    ) {
        $this->authorization = $authorization;
        $this->serializer = $serializer;
        $this->cache = $cache;
        $this->urlBuilder = $urlBuilder;
        $this->logCollectionFactory = $logCollectionFactory;
    }

    public function getIdentity()
    {
        return hash('sha256', 'sfm_unread_marketplace_order_logs');
    }

    private function loadCounts()
    {
        if ((null !== $this->unreadLogsCount) && (null !== $this->ordersWithUnreadLogsCount)) {
            return;
        }

        $cache = $this->cache->load(static::CACHE_KEY);

        if (is_string($cache) && ('' !== $cache)) {
            try {
                $counts = $this->serializer->unserialize($cache);
            } catch (\Exception $e) {
                $counts = null;
            }

            if (is_array($counts)) {
                $this->unreadLogsCount = (int) ($counts[0] ?? 0);
                $this->ordersWithUnreadLogsCount = (int) ($counts[1] ?? 0);
            }
        }

        if ((null === $this->unreadLogsCount) || (null === $this->ordersWithUnreadLogsCount)) {
            $logCollection = $this->logCollectionFactory
                ->create()
                ->addReadFilter(false);

            $this->unreadLogsCount = $logCollection->getSize();
            $this->ordersWithUnreadLogsCount = $logCollection->getOrderCount();

            $this->cache->save(
                $this->serializer->serialize([ $this->unreadLogsCount, $this->ordersWithUnreadLogsCount ]),
                static::CACHE_KEY,
                [],
                3600
            );
        }
    }

    private function getUnreadLogCount()
    {
        $this->loadCounts();

        return $this->unreadLogsCount;
    }

    private function getOrderWithUnreadLogsCount()
    {
        $this->loadCounts();

        return $this->ordersWithUnreadLogsCount;
    }

    public function isDisplayed()
    {
        return $this->authorization->isAllowed(static::ADMIN_RESOURCE) && ($this->getUnreadLogCount() > 0);
    }

    public function getText()
    {
        return implode(
            ' ',
            [
                'Shopping Feed:',
                __(
                    '<strong>%1</strong> marketplace orders have <strong>%2</strong> unread logs.',
                    $this->getOrderWithUnreadLogsCount(),
                    $this->getUnreadLogCount()
                ),
                __(
                    '<a href="%1">Click here to view the logs</a>.',
                    $this->urlBuilder->getUrl(
                        'shoppingfeed_manager/marketplace_order_log/index',
                        [ LogInterface::IS_READ => LogGridCollection::IS_READ_FILTER_VALUE_UNREAD ]
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
