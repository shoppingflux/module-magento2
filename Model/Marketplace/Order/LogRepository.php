<?php

namespace ShoppingFeed\Manager\Model\Marketplace\Order;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Api\Marketplace\Order\LogRepositoryInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\LogInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Log as LogResource;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\LogFactory as LogResourceFactory;

class LogRepository implements LogRepositoryInterface
{
    /**
     * @var LogResource
     */
    protected $logResource;

    /**
     * @var LogFactory
     */
    protected $logFactory;

    /**
     * @param LogResourceFactory $logResourceFactory
     * @param LogFactory $logFactory
     */
    public function __construct(LogResourceFactory $logResourceFactory, LogFactory $logFactory)
    {
        $this->logResource = $logResourceFactory->create();
        $this->logFactory = $logFactory;
    }

    public function save(LogInterface $log)
    {
        try {
            $this->logResource->save($log);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $log;
    }

    public function getById($logId)
    {
        $log = $this->logFactory->create();
        $this->logResource->load($log, $logId);

        if (!$log->getId()) {
            throw new NoSuchEntityException(__('Marketplace order log with ID "%1" does not exist.', $logId));
        }

        return $log;
    }
}
