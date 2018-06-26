<?php

namespace ShoppingFeed\Manager\Api\Marketplace\Order;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\AddressInterface;


/**
 * @api
 */
interface AddressRepositoryInterface
{
    /**
     * @param AddressInterface $address
     * @return AddressInterface
     * @throws CouldNotSaveException
     */
    public function save(AddressInterface $address);

    /**
     * @param int $addressId
     * @return AddressInterface
     * @throws NoSuchEntityException
     */
    public function getById($addressId);
}
