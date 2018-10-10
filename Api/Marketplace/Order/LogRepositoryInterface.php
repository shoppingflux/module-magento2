<?php

namespace ShoppingFeed\Manager\Api\Marketplace\Order;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\LogInterface;

/**
 * @api
 */
interface LogRepositoryInterface
{
    /**
     * @param LogInterface $log
     * @return LogInterface
     * @throws CouldNotSaveException
     */
    public function save(LogInterface $log);

    /**
     * @param int $logId
     * @return LogInterface
     * @throws NoSuchEntityException
     */
    public function getById($logId);
}
