<?php

namespace ShoppingFeed\Manager\Model\Marketplace\Order;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use ShoppingFeed\Manager\Api\Marketplace\Order\AddressRepositoryInterface;
use ShoppingFeed\Manager\Api\Data\Marketplace\Order\AddressInterface;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\Address as AddressResource;
use ShoppingFeed\Manager\Model\ResourceModel\Marketplace\Order\AddressFactory as AddressResourceFactory;

class AddressRepository implements AddressRepositoryInterface
{
    /**
     * @var AddressResource
     */
    protected $addressResource;

    /**
     * @var AddressFactory
     */
    protected $addressFactory;

    /**
     * @param AddressResourceFactory $addressResourceFactory
     * @param AddressFactory $addressFactory
     */
    public function __construct(AddressResourceFactory $addressResourceFactory, AddressFactory $addressFactory)
    {
        $this->addressResource = $addressResourceFactory->create();
        $this->addressFactory = $addressFactory;
    }

    public function save(AddressInterface $address)
    {
        try {
            $this->addressResource->save($address);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $address;
    }

    public function getById($addressId)
    {
        $address = $this->addressFactory->create();
        $this->addressResource->load($address, $addressId);

        if (!$address->getId()) {
            throw new NoSuchEntityException(__('Marketplace order address with ID "%1" does not exist.', $addressId));
        }

        return $address;
    }
}
